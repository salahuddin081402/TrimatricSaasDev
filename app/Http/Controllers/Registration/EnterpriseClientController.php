<?php
/**
 * TMX-ENTC | app/Http/Controllers/Registration/EnterpriseClientController.php
 *
 * Enterprise Client Registration (Step-1 & Step-2 only)
 * - Logic cloned from EntrepreneurController Step-1 & Step-2
 * - Uses same DB tables (registration_master, entrepreneur_details, job_experiences)
 * - registration_master.registration_type = 'enterprise_client'
 * - No Step-3/4/5/6 here (this controller ends at Step-2)
 */

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EnterpriseClientController extends Controller
{
    /* ===================== UTILITIES ===================== */

    private function currentUserId(): ?int
    {
        $forced = config('header.dev_force_user_id');

        // If dev forced user id is set, ALWAYS use it for this module.
        if (is_numeric($forced)) {
            return (int) $forced;
        }

        return Auth::id();
    }

    /** Resolve company by {company} slug | id | model or fallback from user */
    private function resolveCompany($routeCompany): ?object
    {
        if ($routeCompany instanceof \App\Models\SuperAdmin\GlobalSetup\Company) {
            return DB::table('companies')
                ->where('id', $routeCompany->id)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->first();
        }
        if (is_numeric($routeCompany)) {
            return DB::table('companies')
                ->where('id', $routeCompany)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->first();
        }
        if (is_string($routeCompany)) {
            $c = DB::table('companies')
                ->where('slug', $routeCompany)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->first();
            if ($c) {
                return $c;
            }
        }

        $uid = $this->currentUserId();
        if ($uid) {
            $user = DB::table('users')->where('id', $uid)->first();
            if ($user && $user->company_id) {
                return DB::table('companies')
                    ->where('id', $user->company_id)
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->first();
            }
            if ($user && $user->role_id) {
                return DB::table('companies')
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->orderBy('id')
                    ->first();
            }
        }

        return null;
    }

    private function maxImageKB(): int
    {
        return (int) config('registration.max_image_kb', 1024);
    }

    /** Country record for a company (name, short_code). Neutral fallbacks if missing. */
    private function companyCountry(object $companyRow): object
    {
        $c = DB::table('countries')
            ->where('id', $companyRow->country_id)
            ->first(['name', 'short_code']);
        $name  = $c->name ?? 'global';
        $short = strtoupper($c->short_code ?? 'XX');
        return (object) ['name' => $name, 'short_code' => $short];
    }

    /** Country-aware phone validation rule */
    private function phoneRuleForCompany(object $companyRow): array
    {
        $cc = $this->companyCountry($companyRow)->short_code;
        if ($cc === 'BD') {
            // Bangladesh: optional +88, 01[3-9]XXXXXXXX
            return ['required', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'];
        }
        // Generic international: + optional, digits/space/hyphen/() total length 6..20
        return ['required', 'regex:/^\+?[0-9][0-9\s\-\(\)]{5,19}$/'];
    }

    private function isValidPhoneForCompany(object $companyRow, string $val): bool
    {
        $cc = $this->companyCountry($companyRow)->short_code;
        if ($cc === 'BD') {
            return (bool) preg_match('/^(?:\+?88)?01[3-9]\d{8}$/', $val);
        }
        return (bool) preg_match('/^\+?[0-9][0-9\s\-\(\)]{5,19}$/', $val);
    }

    /** Public image base: assets/images/{country-lower}/{slug}/registration */
    private function publicBaseRelPath(object $companyRow): string
    {
        $country    = $this->companyCountry($companyRow);
        $countryDir = Str::of($country->name)->lower()->value();
               $slug       = $companyRow->slug ?? 'company';
        return "assets/images/{$countryDir}/{$slug}/registration";
    }

    private function publicBaseAbsPath(object $companyRow): string
    {
        return public_path($this->publicBaseRelPath($companyRow));
    }

    private function ensureDir(string $absDir): void
    {
        if (!is_dir($absDir)) {
            @mkdir($absDir, 0775, true);
        }
    }

    private function makeFileName(object $companyRow, int $userId, string $field, string $ext): string
    {
        $country     = $this->companyCountry($companyRow);
        $countryCode = $country->short_code ?: 'XX';
        $slug        = $companyRow->slug ?? 'company';
        $ts          = now()->format('YmdHis');

        return "{$countryCode}_{$slug}_{$userId}_{$field}_{$ts}.{$ext}";
    }

    /**
     * Persist exactly one image for a field.
     * Use after a successful DB commit.
     */
    private function persistSingleImage(
        object $companyRow,
        int $userId,
        string $field,
        ?\Illuminate\Http\UploadedFile $upload,
        ?string $tempUrl,
        ?string $existingRelPath = null
    ): ?string {
        if (!$upload && !$tempUrl) {
            return $existingRelPath;
        }

        $baseRel = $this->publicBaseRelPath($companyRow);
        $baseAbs = $this->publicBaseAbsPath($companyRow);
        $this->ensureDir($baseAbs);

        $ext = $upload?->getClientOriginalExtension();
        if (!$ext && $tempUrl) {
            $ext = pathinfo(parse_url($tempUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg';
        }
        $ext = strtolower($ext ?: 'jpg');

        $finalName = $this->makeFileName($companyRow, $userId, $field, $ext);
        $finalAbs  = rtrim($baseAbs, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $finalName;
        $finalRel  = rtrim($baseRel, '/') . '/' . $finalName;

        if ($existingRelPath) {
            @unlink(public_path($existingRelPath));
        }

        if ($upload) {
            $upload->move($baseAbs, $finalName);
        } else {
            $srcPath = public_path(ltrim(parse_url($tempUrl, PHP_URL_PATH) ?? '', '/'));
            @copy($srcPath, $finalAbs);
            @unlink($srcPath);
        }

        return $finalRel;
    }

    /** Preserve temp uploads across validation */
    private function mergedTempUploads(Request $request): array
    {
        $sessionTemps = (array) session('_temp_uploads', []);
        $hiddenTemps  = [];

        foreach (['photo'] as $f) {
            $key = "temp_{$f}";
            $val = trim((string) $request->input($key, ''));
            if ($val !== '') {
                $hiddenTemps[$key] = $val;
            }
        }

        $merged = array_filter($sessionTemps, fn($v) => $v !== null && $v !== '');
        foreach ($hiddenTemps as $k => $v) {
            $merged[$k] = $v;
        }

        return $merged;
    }

    private function backWithInputAndTemp(Request $request, array $errors)
    {
        $temps = $this->mergedTempUploads($request);
        // keep user on step-1 after failure
        session(['enterprise_client.step' => 1]);

        return back()
            ->withErrors($errors)
            ->withInput()
            ->with('_temp_uploads', $temps);
    }

    private function logActivity(string $action, ?int $uid, int $companyId, array $details = [])
    {
        try {
            DB::table('activity_logs')->insert([
                'company_id' => $companyId,
                'user_id'    => $uid,
                'action'     => $action,
                'table_name' => 'registration_master',
                'row_id'     => $details['reg_id'] ?? null,
                'details'    => json_encode($details),
                'ip_address' => request()->ip(),
                'time_local' => now(),
                'time_dhaka' => now(),
                'created_by' => $uid,
                'updated_by' => $uid,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('EnterpriseClient.logActivity', ['err' => $e->getMessage()]);
        }
    }

    /** Build Step-1 hydration payload for Blade */
    private function buildStep1Form(?object $reg, ?object $details, ?object $companyRow): array
    {
        $form = [
            'full_name'       => $reg->full_name ?? ($reg->Full_Name ?? null),
            'email'           => $reg->email ?? ($reg->Email ?? null),
            'gender'          => $reg->gender ?? ($reg->Gender ?? null),
            'date_of_birth'   => isset($reg->date_of_birth)
                ? (string) $reg->date_of_birth
                : (isset($reg->Date_Of_Birth) ? (string) $reg->Date_Of_Birth : null),
            'phone'           => $reg->phone ?? ($reg->Phone ?? null),
            'division_id'     => $reg->division_id ?? ($reg->Division_id ?? $reg->Division_ID ?? null),
            'district_id'     => $reg->district_id ?? ($reg->District_id ?? $reg->District_ID ?? null),
            'upazila_id'      => $reg->upazila_id ?? ($reg->Upazila_id ?? $reg->Upazila_ID ?? null),
            'thana_id'        => $reg->thana_id ?? ($reg->Thana_id ?? $reg->Thana_ID ?? null),
            'person_type'     => $reg->person_type ?? ($reg->Person_Type ?? null),
            'profession'      => $reg->Profession ?? null,
            'present_address' => $reg->present_address ?? ($reg->Present_Address ?? null),

            // business (with lock) – using same columns as entrepreneur_details
            'do_you_have_business'       => isset($details?->do_you_have_business)
                ? (string) $details->do_you_have_business
                : (isset($details?->Do_you_have_business) ? (string) $details->Do_you_have_business : '0'),
            'business_type_id'           => $details->business_type_id ?? ($details->Business_type_id ?? null),
            'company_name'               => $details->Company_name ?? null,
            'company_establishment_year' => $details->Company_Establishment_Year ?? null,
            'company_address'            => $details->Company_address ?? null,
            'company_contact_no'         => $details->Company_contact_no ?? null,
            'turn_over'                  => $details->Turn_over ?? null,

            // photo
            'photo_rel' => $reg->Photo ?? null,
            'photo_url' => (!empty($reg?->Photo) && $companyRow) ? asset($reg->Photo) : null,
        ];

        // Person Type lock: if B → force business = '1'
        if (($form['person_type'] ?? '') === 'B') {
            $form['do_you_have_business'] = '1';
        } elseif ($form['do_you_have_business'] === null) {
            $form['do_you_have_business'] = '0';
        }

        return $form;
    }

    /** Flash DB values into old() so Blade hydrates on clean GET */
    private function flashStep1Old(array $form): void
    {
        $payload = [
            'full_name'                  => $form['full_name'],
            'email'                      => $form['email'],
            'gender'                     => $form['gender'],
            'date_of_birth'              => $form['date_of_birth'],
            'phone'                      => $form['phone'],
            'division_id'                => $form['division_id'],
            'district_id'                => $form['district_id'],
            'upazila_id'                 => $form['upazila_id'],
            'thana_id'                   => $form['thana_id'],
            'person_type'                => $form['person_type'],
            'present_address'            => $form['present_address'],
            'profession'                 => $form['profession'],
            'do_you_have_business'       => $form['do_you_have_business'],
            'business_type_id'           => $form['business_type_id'],
            'company_name'               => $form['company_name'],
            'company_establishment_year' => $form['company_establishment_year'],
            'company_address'            => $form['company_address'],
            'company_contact_no'         => $form['company_contact_no'],
            'turn_over'                  => $form['turn_over'],
        ];

        request()->session()->flashInput(
            array_filter($payload, fn($v) => $v !== null && $v !== '')
        );
    }

    /** Centralized redirect to Step-2 with robust session hand-off */
    private function goStep2(object $companyRow, int $regId)
    {
        session([
            'enterprise_client.step'        => 2,
            'enterprise_client.reg_last'    => $regId,
            'enterprise_client_step1_saved' => true,
            'step1_saved'                   => true,
        ]);

        return redirect()
            ->route('registration.enterprise_client.step2.create', [
                'company' => $companyRow->slug,
                'reg'     => $regId,
            ])
            ->with('success', 'Step-1 saved. Proceed to Step-2.');
    }

    /* ===================== EDIT ENTRY (PUBLIC DASHBOARD / HEADER) ===================== */
    /**
     * Entry point for "Edit Registration" from public dashboard/header.
     * - Uses currentUserId() + company to resolve existing Enterprise Client registration.
     * - Then forwards into the existing Step-1 create flow, which already hydrates from DB.
     */
    public function editEntry(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        $reg = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'enterprise_client')
            ->orderByDesc('id')
            ->first();
        abort_if(!$reg, 404, 'Registration not found');

        // Ensure wizard context points to this registration and starts from Step-1
        session([
            'enterprise_client.reg_last' => (int) $reg->id,
            'enterprise_client.step'     => 1,
        ]);

        return redirect()
            ->route('registration.enterprise_client.step1.create', [
                'company' => $companyRow->slug,
            ])
            ->with('success', 'You can now edit your Enterprise Client registration.');
    }

    /* ===================== AJAX: GEO ===================== */

    public function districtsByDivision(Request $request, $company)
    {
        $divisionId = (int) $request->query('division_id', 0);

        $rows = DB::table('districts')
            ->where('division_id', $divisionId)
            ->orderBy('name')
            ->get(['id', 'name', 'short_code']);

        return response()->json($rows->values());
    }

    public function upazilasByDistrict(Request $request, $company)
    {
        $districtId = (int) $request->query('district_id', 0);

        $rows = DB::table('upazilas')
            ->where('district_id', $districtId)
            ->orderBy('name')
            ->get(['id', 'name', 'short_code']);

        return response()->json($rows->values());
    }

    public function thanasByDistrict(Request $request, $company)
    {
        $districtId = (int) $request->query('district_id', 0);

        $rows = DB::table('thanas')
            ->where('district_id', $districtId)
            ->orderBy('name')
            ->get(['id', 'name', 'short_code']);

        return response()->json($rows->values());
    }

    /* ===================== TEMP UPLOAD ===================== */

    /** Returns JSON {ok:true, field:'photo', url:'/storage/tmp/registration/..'} */
    public function tempUpload(Request $request, $company)
    {
        $uid = $this->currentUserId();
        if (!$uid) {
            return response()->json(['ok' => false, 'message' => 'Login required'], 403);
        }

        $companyRow = $this->resolveCompany($company);
        if (!$companyRow) {
            return response()->json(['ok' => false, 'message' => 'Company not found'], 404);
        }

        $field = $request->input('field');
        if ($field !== 'photo') {
            return response()->json(['ok' => false, 'message' => 'Invalid field'], 400);
        }

        if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
            return response()->json(['ok' => false, 'message' => 'No valid file uploaded'], 400);
        }

        $file     = $request->file('file');
        $maxBytes = $this->maxImageKB() * 1024;
        if (method_exists($file, 'getSize') && $file->getSize() > $maxBytes) {
            return response()->json([
                'ok'      => false,
                'message' => "File too large (max {$this->maxImageKB()}KB)"
            ], 400);
        }

        $ext  = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $name = 'tmp_' . Str::random(10) . '_' . time() . '.' . $ext;
        $path = $file->storeAs('public/tmp/registration', $name);
        $url  = Storage::url($path);

        $temps                  = session('_temp_uploads', []);
        $temps["temp_{$field}"] = $url;
        session(['_temp_uploads' => $temps]);

        return response()->json(['ok' => true, 'field' => $field, 'url' => $url]);
    }

    /* ===================== SINGLE-BLADE FLOW: STEP-1 ===================== */

    /* -------- Step-1 CREATE (Page load) -------- */
    public function create(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');

        // mark step-1 (but do not force if old input already present)
        session(['enterprise_client.step' => 1]);

        // existing reg (latest) if any
        $existing = DB::table('registration_master')
            ->where('company_id', $companyRow->id)
            ->where('user_id', $uid)
            ->where('registration_type', 'enterprise_client')
            ->orderByDesc('id')
            ->first();

        $details = null;
        if ($existing) {
            // reuse entrepreneur_details table structure
            $details = DB::table('entrepreneur_details')
                ->where('company_id', $companyRow->id)
                ->where('registration_id', $existing->id)
                ->first();
        }

        // preload lists
        $divisions = DB::table('divisions')
            ->orderBy('short_code')
            ->get(['id', 'name', 'short_code']);

        $professions = DB::table('professions')
            ->where('status', 1)
            ->orderBy('profession')
            ->get(['id', 'profession']);

        $businessTypes = DB::table('business_types')
            ->where('status', 1)
            ->orderBy('business_type')
            ->get(['business_type_id as id', 'business_type as name']);

        // user ctx (name/email)
        $user = DB::table('users')
            ->where('id', $uid)
            ->first(['name', 'email']);
        abort_if(!$user, 403, 'User not found');

        $ctx = [
            'name'  => $user->name ?: '',
            'email' => $user->email ?: '',
        ];

        // Step-1 form hydration
        $form = $this->buildStep1Form($existing, $details, $companyRow);

        // Only flash DB → old() on clean GET (no overwrite on validation failure)
        if (!$request->session()->has('_old_input')) {
            $this->flashStep1Old($form);
        }

        // Step-2 handoff helpers for the Blade
        $step2Ready = $existing ? true : false;
        $step2Url   = $existing
            ? route('registration.enterprise_client.step2.create', [
                'company' => $companyRow->slug,
                'reg'     => (int) $existing->id,
            ])
            : '';

        return view('registration.enterprise_client.create', [
            'title'         => "{$companyRow->name} | Enterprise Client Registration — Step 1",
            'company'       => $companyRow,
            'divisions'     => $divisions,
            'professions'   => $professions,
            'businessTypes' => $businessTypes,
            'tempUploads'   => session('_temp_uploads', []),
            'ctx'           => $ctx,
            'existing'      => $existing,
            'details'       => $details,
            'form'          => $form,
            'step2Ready'    => $step2Ready,
            'step2Url'      => $step2Url,
            'step'          => 1,
        ]);
    }

    /**
     * Store/Update Step-1:
     * - If no existing row: insert registration_master + entrepreneur_details (reused) for enterprise_client
     * - If existing row for this user+company: VALIDATE & UPDATE it, then go Step-2
     */
    public function store(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        $existing = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'enterprise_client')
            ->orderByDesc('id')
            ->first();

        $allowedMimes = 'jpg,jpeg,png,webp,gif,jfif,bmp';
        $maxKB        = $this->maxImageKB();
        $currentYear  = (int) date('Y');

        /* ===== Validation rules (country-aware) ===== */
        $rules = [
            // Personal Info (registration_master)
            'gender'        => ['required', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],

            'phone' => $this->phoneRuleForCompany($companyRow),
            'email' => ['nullable', 'email', 'max:190'],

            'division_id' => ['required', 'integer', 'exists:divisions,id'],
            'district_id' => [
                'required',
                'integer',
                Rule::exists('districts', 'id')->where(
                    fn($q) => $q->where('division_id', $request->input('division_id'))
                ),
            ],
            'upazila_id' => [
                'required',
                'integer',
                Rule::exists('upazilas', 'id')->where(
                    fn($q) => $q->where('district_id', $request->input('district_id'))
                ),
            ],
            'thana_id' => [
                'required',
                'integer',
                Rule::exists('thanas', 'id')->where(
                    fn($q) => $q->where('district_id', $request->input('district_id'))
                ),
            ],

            'person_type'     => ['required', Rule::in(['J', 'B', 'H', 'S', 'P', 'O'])],
            'present_address' => ['required', 'string', 'max:255'],
            'profession'      => ['required', 'integer', 'exists:professions,id'],

            'photo'      => ['nullable', 'file', 'mimes:' . $allowedMimes, 'max:' . $maxKB],
            'temp_photo' => ['nullable', 'string'],

            // Business Info (entrepreneur_details structure reused)
            'do_you_have_business'       => ['required', Rule::in(['0', '1', 0, 1])],
            'business_type_id'           => ['nullable', 'integer', 'exists:business_types,business_type_id'],
            'company_name'               => ['nullable', 'string', 'max:150'],
            'company_establishment_year' => ['nullable', 'integer', 'min:1950', 'max:' . $currentYear],
            'company_address'            => ['nullable', 'string', 'max:255'],
            'company_contact_no'         => ['nullable', 'string', 'max:30'], // checked in after()
            'turn_over'                  => ['nullable', 'string', 'max:50'],
        ];

        // Photo required when no temp AND no existing record (create mode) or when existing has no photo.
        if ($existing) {
            if (empty($existing->Photo)) {
                $rules['photo'][] = 'required_without:temp_photo';
            }
        } else {
            $rules['photo'][] = 'required_without:temp_photo';
        }

        $cc = $this->companyCountry($companyRow)->short_code;
        $messages = [
            'gender.required'          => 'Select gender.',
            'phone.regex'              => $cc === 'BD'
                ? 'Enter a valid Bangladesh mobile number (01XXXXXXXXX).'
                : 'Enter a valid phone number.',
            'division_id.required'     => 'Select division.',
            'district_id.required'     => 'Select district.',
            'upazila_id.required'      => 'Select upazila.',
            'thana_id.required'        => 'Select thana.',
            'person_type.required'     => 'Select person type.',
            'profession.required'      => 'Select a profession.',
            'present_address.required' => 'Enter present address.',
            'photo.required_without'   => 'Photo image is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        // business dependencies and person_type lock
        $validator->after(function ($v) use ($request, $companyRow) {
            $pt  = (string) $request->input('person_type', '');
            $biz = (string) $request->input('do_you_have_business', '0');

            if ($pt === 'B' && $biz !== '1') {
                $v->errors()->add(
                    'do_you_have_business',
                    'You selected Business man as Person Type. Business must be Yes.'
                );
            }

            if ($biz === '1') {
                foreach ([
                    'business_type_id'           => 'Select a business type.',
                    'company_name'               => 'Enter company name.',
                    'company_establishment_year' => 'Select establishment year.',
                    'company_address'            => 'Enter company address.',
                    'company_contact_no'         => $this->companyCountry($companyRow)->short_code === 'BD'
                        ? 'Enter a valid company contact number (BD).'
                        : 'Enter a valid company contact number.',
                    'turn_over'                  => 'Select a turnover range.',
                ] as $field => $msg) {
                    $val = $request->input($field, null);
                    if ($field === 'company_contact_no') {
                        if (!empty($val) && !$this->isValidPhoneForCompany($companyRow, (string) $val)) {
                            $v->errors()->add($field, $msg);
                        }
                    } elseif ($val === null || $val === '') {
                        $v->errors()->add($field, $msg);
                    }
                }
            }
        });

        if ($validator->fails()) {
            return $this->backWithInputAndTemp($request, $validator->errors()->toArray());
        }

        $data = $validator->validated();

        // Uniqueness inside company (ignore self if updating)
        if ($existing) {
            $dupPhone = DB::table('registration_master')
                ->where('company_id', $companyId)
                ->where('phone', $data['phone'])
                ->where('id', '!=', $existing->id)
                ->exists();
            if ($dupPhone) {
                return $this->backWithInputAndTemp($request, [
                    'phone' => 'This mobile number is already registered for this company.',
                ]);
            }
            if (!empty($data['email'])) {
                $dupEmail = DB::table('registration_master')
                    ->where('company_id', $companyId)
                    ->where('email', $data['email'])
                    ->where('id', '!=', $existing->id)
                    ->exists();
                if ($dupEmail) {
                    return $this->backWithInputAndTemp($request, [
                        'email' => 'This email is already registered for this company.',
                    ]);
                }
            }
        } else {
            $dupPhone = DB::table('registration_master')
                ->where('company_id', $companyId)
                ->where('phone', $data['phone'])
                ->exists();
            if ($dupPhone) {
                return $this->backWithInputAndTemp($request, [
                    'phone' => 'This mobile number is already registered for this company.',
                ]);
            }
            if (!empty($data['email'])) {
                $dupEmail = DB::table('registration_master')
                    ->where('company_id', $companyId)
                    ->where('email', $data['email'])
                    ->exists();
            }
            if (!empty($data['email']) && $dupEmail) {
                return $this->backWithInputAndTemp($request, [
                    'email' => 'This email is already registered for this company.',
                ]);
            }
        }

        // Prepare image plan
        $plan = [
            'photo' => [$request->file('photo'), $data['temp_photo'] ?? null, $existing->Photo ?? null],
        ];

        $fullName = (string) ($request->input('full_name', '') ?: '');
        $email    = $data['email'] ?? null;
        $phone    = $data['phone'];

        $regId = $existing->id ?? null;

        try {
            DB::transaction(function () use (
                $uid,
                $companyId,
                $companyRow,
                &$regId,
                $data,
                $fullName,
                $email,
                $phone,
                $existing
            ) {
                if ($existing) {
                    // UPDATE path
                    DB::table('registration_master')
                        ->where('id', $existing->id)
                        ->update([
                            'full_name'       => $fullName,
                            'gender'          => $data['gender'],
                            'date_of_birth'   => $data['date_of_birth'] ?? null,
                            'phone'           => $phone,
                            'email'           => $email,
                            'division_id'     => (int) $data['division_id'],
                            'district_id'     => (int) $data['district_id'],
                            'upazila_id'      => (int) $data['upazila_id'],
                            'thana_id'        => (int) $data['thana_id'],
                            'person_type'     => $data['person_type'],
                            'Profession'      => (int) $data['profession'],
                            'present_address' => $data['present_address'],
                            'updated_by'      => $uid,
                            'updated_at'      => now(),
                        ]);

                    $details = DB::table('entrepreneur_details')
                        ->where('company_id', $companyId)
                        ->where('registration_id', $existing->id)
                        ->first();

                    $biz     = (string) ($data['do_you_have_business'] ?? '0');
                    $mustBiz = $data['person_type'] === 'B' ? '1' : $biz;

                    $payload = [
                        'company_id'                 => $companyId,
                        'registration_id'            => $existing->id,
                        'do_you_have_business'       => (int) $mustBiz,
                        'business_type_id'           => $mustBiz === '1'
                            ? (int) ($data['business_type_id'] ?? 0)
                            : null,
                        'Company_name'               => $mustBiz === '1'
                            ? ($data['company_name'] ?? null)
                            : null,
                        'Company_Establishment_Year' => $mustBiz === '1'
                            ? (int) ($data['company_establishment_year'] ?? 0)
                            : null,
                        'Company_address'            => $mustBiz === '1'
                            ? ($data['company_address'] ?? null)
                            : null,
                        'Company_contact_no'         => $mustBiz === '1'
                            ? ($data['company_contact_no'] ?? null)
                            : null,
                        'Turn_over'                  => $mustBiz === '1'
                            ? ($data['turn_over'] ?? null)
                            : null,
                        'updated_at'                 => now(),
                    ];

                    if ($details) {
                        DB::table('entrepreneur_details')
                            ->where('company_id', $companyId)
                            ->where('registration_id', $existing->id)
                            ->update($payload);
                    } else {
                        $payload['created_at'] = now();
                        DB::table('entrepreneur_details')->insert($payload);
                    }

                    $regId = (int) $existing->id;

                    $this->logActivity('update', $uid, $companyId, [
                        'type'   => 'enterprise_client',
                        'reg_id' => $regId,
                    ]);
                } else {
                    // CREATE path
                    $regId = DB::table('registration_master')->insertGetId([
                        'company_id'        => $companyId,
                        'user_id'           => $uid,
                        'registration_type' => 'enterprise_client',
                        'full_name'         => $fullName,
                        'gender'            => $data['gender'],
                        'date_of_birth'     => $data['date_of_birth'] ?? null,
                        'phone'             => $phone,
                        'email'             => $email,
                        'division_id'       => (int) $data['division_id'],
                        'district_id'       => (int) $data['district_id'],
                        'upazila_id'        => (int) $data['upazila_id'],
                        'thana_id'          => (int) $data['thana_id'],
                        'person_type'       => $data['person_type'],
                        'Profession'        => (int) $data['profession'],
                        'present_address'   => $data['present_address'],
                        'approval_status'   => 'approved',
                        'status'            => 1,
                        'created_by'        => $uid,
                        'updated_by'        => $uid,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);

                    // NOTE: role change intentionally omitted here.
                    // If Enterprise Client should set a role, add it explicitly later.

                    $biz     = (string) ($data['do_you_have_business'] ?? '0');
                    $mustBiz = $data['person_type'] === 'B' ? '1' : $biz;

                    DB::table('entrepreneur_details')->insert([
                        'company_id'                 => $companyId,
                        'registration_id'            => $regId,
                        'do_you_have_business'       => (int) $mustBiz,
                        'business_type_id'           => $mustBiz === '1'
                            ? (int) ($data['business_type_id'] ?? 0)
                            : null,
                        'Company_name'               => $mustBiz === '1'
                            ? ($data['company_name'] ?? null)
                            : null,
                        'Company_Establishment_Year' => $mustBiz === '1'
                            ? (int) ($data['company_establishment_year'] ?? 0)
                            : null,
                        'Company_address'            => $mustBiz === '1'
                            ? ($data['company_address'] ?? null)
                            : null,
                        'Company_contact_no'         => $mustBiz === '1'
                            ? ($data['company_contact_no'] ?? null)
                            : null,
                        'Turn_over'                  => $mustBiz === '1'
                            ? ($data['turn_over'] ?? null)
                            : null,
                        'created_at'                 => now(),
                        'updated_at'                 => now(),
                    ]);

                    DB::table('users')
                        ->where('company_id', $companyId)
                        ->where('id', $uid)
                        ->update([
                            'role_id'    => 13,
                            'updated_at' => now(),
                        ]);

                    $this->logActivity('create', $uid, $companyId, [
                        'type'   => 'enterprise_client',
                        'reg_id' => $regId,
                    ]);
                }
            });

            // After commit: persist image and update master
            DB::afterCommit(function () use ($uid, $companyRow, $regId, $data, $existing, $plan) {
                if (!$regId) {
                    return;
                }
                $photoRel = $this->persistSingleImage(
                    $companyRow,
                    $uid,
                    'photo',
                    $plan['photo'][0],
                    $plan['photo'][1],
                    $plan['photo'][2] ?? null
                );
                if ($photoRel) {
                    DB::table('registration_master')
                        ->where('id', $regId)
                        ->update([
                            'Photo'      => $photoRel,
                            'updated_at' => now(),
                        ]);
                }
            });
        } catch (QueryException $e) {
            try {
                \Log::error('EnterpriseClient.store QueryException', [
                    'sql'      => method_exists($e, 'getSql') ? $e->getSql() : null,
                    'bindings' => method_exists($e, 'getBindings') ? $e->getBindings() : null,
                    'message'  => $e->getMessage(),
                ]);
            } catch (\Throwable $ignore) {
            }
            $msg = $e->getMessage();

            if (str_contains($msg, 'uq_reg_company_phone') || str_contains($msg, 'phone')) {
                return $this->backWithInputAndTemp($request, [
                    'phone' => 'This mobile number is already registered for this company.',
                ]);
            }
            if (str_contains($msg, 'uq_reg_company_email') || str_contains($msg, 'email')) {
                return $this->backWithInputAndTemp($request, [
                    'email' => 'This email is already registered for this company.',
                ]);
            }
            if (str_contains($msg, 'uq_company_reg')) {
                return $this->backWithInputAndTemp($request, [
                    '_error' => 'You already submitted Enterprise Client details for this registration.',
                ]);
            }

            return $this->backWithInputAndTemp($request, [
                '_error' => 'Failed to save. Please try again.',
            ]);
        } catch (\Throwable $e) {
            \Log::error('EnterpriseClient.store Throwable', ['err' => $e->getMessage()]);
            return $this->backWithInputAndTemp($request, [
                '_error' => 'Failed to save. Please try again.',
            ]);
        }

        session()->forget('_temp_uploads');

        // Advance to Step-2 with session flags
        session(['enterprise_client_step1_saved' => true]);

        return $this->goStep2($companyRow, (int) $regId);
    }

    /* -------- EDIT (still uses the single Blade) -------- */
    public function edit(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        $row = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'enterprise_client')
            ->orderByDesc('id')
            ->first();
        abort_if(!$row, 404, 'Registration not found');

        $details = DB::table('entrepreneur_details')
            ->where('company_id', $companyId)
            ->where('registration_id', $row->id)
            ->first();

        // user ctx
        $user = DB::table('users')
            ->where('id', $uid)
            ->first(['name', 'email']);
        $ctx = [
            'name'  => $user->name ?? '',
            'email' => $user->email ?? '',
        ];

        // dropdown sources
        $divisions = DB::table('divisions')
            ->orderBy('short_code')
            ->get(['id', 'name', 'short_code']);

        $professions = DB::table('professions')
            ->where('status', 1)
            ->orderBy('profession')
            ->get(['id', 'profession']);

        $businessTypes = DB::table('business_types')
            ->where('status', 1)
            ->orderBy('business_type')
            ->get(['business_type_id as id', 'business_type as name']);

        // prefer query step, then session flag, else 1 (clamp to [1,2])
        $stepFromQuery = (int) $request->query('step', 0);
        $stepFromSess  = (int) session('enterprise_client.step', 1);
        $step          = $stepFromQuery > 0
            ? $stepFromQuery
            : ($stepFromSess > 0 ? $stepFromSess : 1);
        if (!in_array($step, [1, 2], true)) {
            $step = 1;
        }

        // hydration form
        $form = $this->buildStep1Form($row, $details, $companyRow);

        // only flash DB → old() on clean GET (no overwrite on validation failure)
        if (!$request->session()->has('_old_input')) {
            $this->flashStep1Old($form);
        }

        // Step-2 helpers for Blade (when editing, Step-2 is always reachable)
        $step2Ready = true;
        $step2Url   = route('registration.enterprise_client.step2.create', [
            'company' => $companyRow->slug,
            'reg'     => (int) $row->id,
        ]);

        // keep session aligned
        session([
            'enterprise_client.reg_last' => (int) $row->id,
            'enterprise_client.step'     => $step ?: 1,
        ]);

        return view('registration.enterprise_client.create', [
            'title'         => "{$companyRow->name} | Enterprise Client Registration — Edit",
            'company'       => $companyRow,
            'row'           => $row,
            'details'       => $details,
            'divisions'     => $divisions,
            'professions'   => $professions,
            'businessTypes' => $businessTypes,
            'tempUploads'   => session('_temp_uploads', []),
            'ctx'           => $ctx,
            'form'          => $form,
            'step2Ready'    => $step2Ready,
            'step2Url'      => $step2Url,
            'step'          => $step,
            'reg'           => $row,
        ]);
    }

    /**
     * Update Step-1 fields (single Blade).
     */
    public function update(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        $row = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'enterprise_client')
            ->orderByDesc('id')
            ->first();
        abort_if(!$row, 404, 'Registration not found');

        $allowedMimes = 'jpg,jpeg,png,webp,gif,jfif,bmp';
        $maxKB        = $this->maxImageKB();
        $currentYear  = (int) date('Y');

        $rules = [
            'gender'        => ['required', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],

            'phone' => $this->phoneRuleForCompany($companyRow),
            'email' => ['nullable', 'email', 'max:190'],

            'division_id' => ['required', 'integer', 'exists:divisions,id'],
            'district_id' => [
                'required',
                'integer',
                Rule::exists('districts', 'id')->where(
                    fn($q) => $q->where('division_id', $request->input('division_id'))
                ),
            ],
            'upazila_id' => [
                'required',
                'integer',
                Rule::exists('upazilas', 'id')->where(
                    fn($q) => $q->where('district_id', $request->input('district_id'))
                ),
            ],
            'thana_id' => [
                'required',
                'integer',
                Rule::exists('thanas', 'id')->where(
                    fn($q) => $q->where('district_id', $request->input('district_id'))
                ),
            ],

            'person_type'     => ['required', Rule::in(['J', 'B', 'H', 'S', 'P', 'O'])],
            'present_address' => ['required', 'string', 'max:255'],
            'profession'      => ['required', 'integer', 'exists:professions,id'],

            'photo'      => ['nullable', 'file', 'mimes:' . $allowedMimes, 'max:' . $maxKB],
            'temp_photo' => ['nullable', 'string'],

            'do_you_have_business'       => ['required', Rule::in(['0', '1', 0, 1])],
            'business_type_id'           => ['nullable', 'integer', 'exists:business_types,business_type_id'],
            'company_name'               => ['nullable', 'string', 'max:150'],
            'company_establishment_year' => ['nullable', 'integer', 'min:1950', 'max:' . $currentYear],
            'company_address'            => ['nullable', 'string', 'max:255'],
            'company_contact_no'         => ['nullable', 'string', 'max:30'], // checked in after()
            'turn_over'                  => ['nullable', 'string', 'max:50'],
        ];

        $cc = $this->companyCountry($companyRow)->short_code;
        $messages = [
            'gender.required'          => 'Select gender.',
            'phone.regex'              => $cc === 'BD'
                ? 'Enter a valid Bangladesh mobile number (01XXXXXXXXX).'
                : 'Enter a valid phone number.',
            'division_id.required'     => 'Select division.',
            'district_id.required'     => 'Select district.',
            'upazila_id.required'      => 'Select upazila.',
            'thana_id.required'        => 'Select thana.',
            'person_type.required'     => 'Select person type.',
            'profession.required'      => 'Select a profession.',
            'present_address.required' => 'Enter present address.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        $validator->after(function ($v) use ($request, $companyRow) {
            $pt  = (string) $request->input('person_type', '');
            $biz = (string) $request->input('do_you_have_business', '0');

            if ($pt === 'B' && $biz !== '1') {
                $v->errors()->add(
                    'do_you_have_business',
                    'You selected Business man as Person Type. Business must be Yes.'
                );
            }

            if ($biz === '1') {
                foreach ([
                    'business_type_id'           => 'Select a business type.',
                    'company_name'               => 'Enter company name.',
                    'company_establishment_year' => 'Select establishment year.',
                    'company_address'            => 'Enter company address.',
                    'company_contact_no'         => $this->companyCountry($companyRow)->short_code === 'BD'
                        ? 'Enter a valid company contact number (BD).'
                        : 'Enter a valid company contact number.',
                    'turn_over'                  => 'Select a turnover range.',
                ] as $field => $msg) {
                    $val = $request->input($field, null);
                    if ($field === 'company_contact_no') {
                        if (!empty($val) && !$this->isValidPhoneForCompany($companyRow, (string) $val)) {
                            $v->errors()->add($field, $msg);
                        }
                    } elseif ($val === null || $val === '') {
                        $v->errors()->add($field, $msg);
                    }
                }
            }
        });

        if ($validator->fails()) {
            return $this->backWithInputAndTemp($request, $validator->errors()->toArray());
        }

        $data = $validator->validated();

        // uniques ignoring current row
        $dupPhone = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('phone', $data['phone'])
            ->where('id', '!=', $row->id)
            ->exists();
        if ($dupPhone) {
            return $this->backWithInputAndTemp($request, [
                'phone' => 'This mobile number is already registered for this company.',
            ]);
        }
        if (!empty($data['email'])) {
            $dupEmail = DB::table('registration_master')
                ->where('company_id', $companyId)
                ->where('email', $data['email'])
                ->where('id', '!=', $row->id)
                ->exists();
            if ($dupEmail) {
                return $this->backWithInputAndTemp($request, [
                    'email' => 'This email is already registered for this company.',
                ]);
            }
        }

        // photo parity
        $hasPhotoExisting = filled($row->Photo);
        $hasPhotoNew      = $request->hasFile('photo') || filled($data['temp_photo'] ?? null);
        if (!$hasPhotoExisting && !$hasPhotoNew) {
            return $this->backWithInputAndTemp($request, [
                'photo' => 'Photo image is required.',
            ]);
        }

        $plan = [
            'photo' => [$request->file('photo'), $data['temp_photo'] ?? null, $row->Photo],
        ];

        try {
            DB::transaction(function () use ($uid, $companyId, $row, $data) {
                DB::table('registration_master')
                    ->where('id', $row->id)
                    ->update([
                        'gender'          => $data['gender'],
                        'date_of_birth'   => $data['date_of_birth'] ?? null,
                        'phone'           => $data['phone'],
                        'email'           => $data['email'] ?? null,
                        'division_id'     => (int) $data['division_id'],
                        'district_id'     => (int) $data['district_id'],
                        'upazila_id'      => (int) $data['upazila_id'],
                        'thana_id'        => (int) $data['thana_id'],
                        'person_type'     => $data['person_type'],
                        'Profession'      => (int) $data['profession'],
                        'present_address' => $data['present_address'],
                        'updated_by'      => $uid,
                        'updated_at'      => now(),
                    ]);

                $details = DB::table('entrepreneur_details')
                    ->where('company_id', $companyId)
                    ->where('registration_id', $row->id)
                    ->first();

                $biz     = (string) ($data['do_you_have_business'] ?? '0');
                $mustBiz = $data['person_type'] === 'B' ? '1' : $biz;

                $payload = [
                    'company_id'                 => $companyId,
                    'registration_id'            => $row->id,
                    'do_you_have_business'       => (int) $mustBiz,
                    'business_type_id'           => $mustBiz === '1'
                        ? (int) ($data['business_type_id'] ?? 0)
                        : null,
                    'Company_name'               => $mustBiz === '1'
                        ? ($data['company_name'] ?? null)
                        : null,
                    'Company_Establishment_Year' => $mustBiz === '1'
                        ? (int) ($data['company_establishment_year'] ?? 0)
                        : null,
                    'Company_address'            => $mustBiz === '1'
                        ? ($data['company_address'] ?? null)
                        : null,
                    'Company_contact_no'         => $mustBiz === '1'
                        ? ($data['company_contact_no'] ?? null)
                        : null,
                    'Turn_over'                  => $mustBiz === '1'
                        ? ($data['turn_over'] ?? null)
                        : null,
                    'updated_at'                 => now(),
                ];

                if ($details) {
                    DB::table('entrepreneur_details')
                        ->where('company_id', $companyId)
                        ->where('registration_id', $row->id)
                        ->update($payload);
                } else {
                    $payload['created_at'] = now();
                    DB::table('entrepreneur_details')->insert($payload);
                }

                $this->logActivity('update', $uid, $companyId, [
                    'type'   => 'enterprise_client',
                    'reg_id' => $row->id,
                ]);
            });

            DB::afterCommit(function () use ($uid, $companyRow, $row, $data, $plan) {
                $photoRel = $this->persistSingleImage(
                    $companyRow,
                    $uid,
                    'photo',
                    $plan['photo'][0],
                    $plan['photo'][1],
                    $plan['photo'][2]
                );
                if ($photoRel) {
                    DB::table('registration_master')
                        ->where('id', $row->id)
                        ->update([
                            'Photo'      => $photoRel,
                            'updated_at' => now(),
                        ]);
                }
            });
        } catch (QueryException $e) {
            try {
                \Log::error('EnterpriseClient.update QueryException', [
                    'sql'      => method_exists($e, 'getSql') ? $e->getSql() : null,
                    'bindings' => method_exists($e, 'getBindings') ? $e->getBindings() : null,
                    'message'  => $e->getMessage(),
                ]);
            } catch (\Throwable $ignore) {
            }

            $msg = $e->getMessage();

            if (str_contains($msg, 'uq_reg_company_phone') || str_contains($msg, 'phone')) {
                return $this->backWithInputAndTemp($request, [
                    'phone' => 'This mobile number is already registered for this company.',
                ]);
            }
            if (str_contains($msg, 'uq_reg_company_email') || str_contains($msg, 'email')) {
                return $this->backWithInputAndTemp($request, [
                    'email' => 'This email is already registered for this company.',
                ]);
            }
            if (str_contains($msg, 'uq_company_reg')) {
                return $this->backWithInputAndTemp($request, [
                    '_error' => 'You already submitted Enterprise Client details for this registration.',
                ]);
            }

            return $this->backWithInputAndTemp($request, [
                '_error' => 'Failed to update. Please try again.',
            ]);
        } catch (\Throwable $e) {
            \Log::error('EnterpriseClient.update Throwable', ['err' => $e->getMessage()]);
            return $this->backWithInputAndTemp($request, [
                '_error' => 'Failed to update. Please try again.',
            ]);
        }

        session()->forget('_temp_uploads');

        // Advance to Step-2
        session(['enterprise_client_step1_saved' => true]);

        return $this->goStep2($companyRow, (int) $row->id);
    }

    /* ===================== STEP-1 WRAPPERS (routes compatibility) ===================== */

    public function step1Create(Request $request, $company)
    {
        // Always render Step-1; do not auto-bounce to Step-2
        return $this->create($request, $company);
    }

    public function step1Store(Request $request, $company)
    {
        // Delegates to store(); handles both create + update then proceeds to step-2.
        return $this->store($request, $company);
    }

    /* ===================== STEP-2: PRESENT JOB (single Blade, last step) ===================== */

    public function step2Create(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        // Resolve the registration: prefer query ?reg=, else session hand-off
        $regId = (int) ($request->query('reg') ?: session('enterprise_client.reg_last', 0));

        $reg = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'enterprise_client')
            ->when($regId > 0, fn($q) => $q->where('id', $regId))
            ->orderByDesc('id')
            ->first();
        abort_if(!$reg, 404, 'Registration not found');

        $regId = (int) $reg->id;

        // Load existing present-job (if any)
        $presentJob = DB::table('job_experiences')
            ->where('Company_id', $companyId)
            ->where('registration_id', $regId)
            ->where('is_present_job', 'Y')
            ->orderByDesc('id')
            ->first();

        // If we are landing here via clean GET (no validation failure),
        // flash DB values → old() so Blade can hydrate Step-2 fields.
        if (!$request->session()->has('_old_input') && $presentJob) {
            $request->session()->flashInput([
                'employer'     => $presentJob->Employer ?? '',
                'job_title'    => $presentJob->Job_title ?? '',
                'department'   => $presentJob->department ?? '',
                'joining_date' => $presentJob->Joining_date ?? '',
                'reg'          => $regId,
            ]);
        }

        // keep session aligned for the Blade to know it's step-2
        session([
            'enterprise_client.step'     => 2,
            'enterprise_client.reg_last' => $regId,
        ]);

        // The unified blade needs its common lists/context
        $divisions = DB::table('divisions')
            ->orderBy('short_code')
            ->get(['id', 'name', 'short_code']);

        $professions = DB::table('professions')
            ->where('status', 1)
            ->orderBy('profession')
            ->get(['id', 'profession']);

        $businessTypes = DB::table('business_types')
            ->where('status', 1)
            ->orderBy('business_type')
            ->get(['business_type_id as id', 'business_type as name']);

        $user = DB::table('users')
            ->where('id', $uid)
            ->first(['name', 'email']);
        $ctx  = [
            'name'  => $user->name ?? '',
            'email' => $user->email ?? '',
        ];

        return view('registration.enterprise_client.create', [
            'title'         => "{$companyRow->name} | Enterprise Client Registration — Step 2",
            'company'       => $companyRow,
            'divisions'     => $divisions,
            'professions'   => $professions,
            'businessTypes' => $businessTypes,
            'tempUploads'   => session('_temp_uploads', []),
            'ctx'           => $ctx,
            'existing'      => $reg,
            'reg'           => $reg,
            'step'          => 2,
            'presentJob'    => $presentJob,
        ]);
    }

    public function step2Store(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        // Resolve the registration strictly for this user+company
        $regId = (int) (
            $request->input('reg')
            ?: $request->query('reg')
            ?: session('enterprise_client.reg_last', 0)
        );

        $reg = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'enterprise_client')
            ->when($regId > 0, fn($q) => $q->where('id', $regId))
            ->orderByDesc('id')
            ->first();
        abort_if(!$reg, 404, 'Registration not found');

        $regId = (int) $reg->id;

        // Read inputs
        $employer   = trim((string) $request->input('employer', ''));
        $jobTitle   = trim((string) $request->input('job_title', ''));
        $department = trim((string) $request->input('department', ''));
        $joining    = trim((string) $request->input('joining_date', ''));

        // If all empty → treat as "no present job", registration complete
        $allEmpty = (
            $employer === ''
            && $jobTitle === ''
            && $department === ''
            && $joining === ''
        );

        if ($allEmpty) {
            session([
                'enterprise_client.step'      => 2,
                'enterprise_client.reg_last'  => $regId,
                'enterprise_client.completed' => true,
            ]);

            return redirect()
                ->route('registration.enterprise_client.step2.create', [
                    'company' => $companyRow->slug,
                    'reg'     => $regId,
                ])
                ->with('success', 'Registration completed without Present Job.');
        }

        // If any present → all required
        $rules = [
            'employer'     => ['required', 'string', 'max:180'],
            'job_title'    => ['required', 'string', 'max:150'],
            'department'   => ['required', 'string', 'max:100'],
            'joining_date' => ['required', 'date', 'before_or_equal:today'],
        ];
        $messages = [
            'employer.required'            => 'Enter Employer.',
            'job_title.required'           => 'Enter Job title.',
            'department.required'          => 'Enter Department.',
            'joining_date.required'        => 'Select Joining date.',
            'joining_date.before_or_equal' => 'Joining date cannot be in the future.',
        ];
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator->errors()->toArray())
                ->withInput();
        }

        $v = $validator->validated();

        // Existing present-job row (if any) for upsert behavior
        $presentJob = DB::table('job_experiences')
            ->where('Company_id', $companyId)
            ->where('registration_id', $regId)
            ->where('is_present_job', 'Y')
            ->first();

        try {
            DB::transaction(function () use ($uid, $companyId, $regId, $v, $presentJob) {
                if ($presentJob) {
                    // UPDATE existing present job
                    DB::table('job_experiences')
                        ->where('Company_id', $companyId)
                        ->where('registration_id', $regId)
                        ->where('is_present_job', 'Y')
                        ->update([
                            'Employer'        => $v['employer'],
                            'Job_title'       => $v['job_title'],
                            'department'      => $v['department'],
                            'Joining_date'    => $v['joining_date'],
                            'End_date'        => null,
                            'status'          => 1,
                            'updated_by'      => $uid,
                            'updated_at'      => now(),
                        ]);
                } else {
                    // INSERT new present job
                    DB::table('job_experiences')->insert([
                        'Company_id'      => $companyId,
                        'registration_id' => $regId,
                        'Employer'        => $v['employer'],
                        'Job_title'       => $v['job_title'],
                        'department'      => $v['department'],
                        'Joining_date'    => $v['joining_date'],
                        'End_date'        => null,
                        'is_present_job'  => 'Y',
                        'status'          => 1,
                        'created_by'      => $uid,
                        'updated_by'      => $uid,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            });

            $this->logActivity('create', $uid, $companyId, [
                'type'   => 'enterprise_client.step2.present_job',
                'reg_id' => $regId,
            ]);
        } catch (QueryException $e) {
            \Log::error('EnterpriseClient.step2Store QueryException', [
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['_error' => 'Failed to save Present Job. Please try again.'])
                ->withInput();
        } catch (\Throwable $e) {
            \Log::error('EnterpriseClient.step2Store Throwable', ['err' => $e->getMessage()]);

            return back()
                ->withErrors(['_error' => 'Failed to save Present Job. Please try again.'])
                ->withInput();
        }

        // Success → this is the LAST step
        session([
            'enterprise_client.step'      => 2,
            'enterprise_client.reg_last'  => $regId,
            'enterprise_client.completed' => true,
        ]);

        return redirect()
            ->route('registration.enterprise_client.step2.create', [
                'company' => $companyRow->slug,
                'reg'     => $regId,
            ])
            ->with('success', 'Present Job saved. Enterprise Client registration is complete. After approval by Head Office you will get the Enterprise Card. You can Edit the registration anytime.');
    }
}
