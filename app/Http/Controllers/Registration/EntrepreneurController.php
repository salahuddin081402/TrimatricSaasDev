<?php

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

class EntrepreneurController extends Controller
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
        session(['entrepreneur.step' => 1]);

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
            \Log::warning('Entrepreneur.logActivity', ['err' => $e->getMessage()]);
        }
    }

    /** Build Step-1 hydration payload for Blade */
    private function buildStep1Form(?object $reg, ?object $entre, ?object $companyRow): array
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

            // business (with lock)
            'do_you_have_business'       => isset($entre?->do_you_have_business)
                ? (string) $entre->do_you_have_business
                : (isset($entre?->Do_you_have_business) ? (string) $entre->Do_you_have_business : '0'),
            'business_type_id'           => $entre->business_type_id ?? ($entre->Business_type_id ?? null),
            'company_name'               => $entre->Company_name ?? null,
            'company_establishment_year' => $entre->Company_Establishment_Year ?? null,
            'company_address'            => $entre->Company_address ?? null,
            'company_contact_no'         => $entre->Company_contact_no ?? null,
            'turn_over'                  => $entre->Turn_over ?? null,

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
            'entrepreneur.step'        => 2,
            'entrepreneur.reg_last'    => $regId,
            'entrepreneur_step1_saved' => true,
            'step1_saved'              => true,
        ]);

        return redirect()
            ->route('registration.entrepreneur.step2.create', [
                'company' => $companyRow->slug,
                'reg'     => $regId,
            ])
            ->with('success', 'Step-1 saved. Proceed to Step-2.');
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
        session(['entrepreneur.step' => 1]);

        // existing reg (latest) if any
        $existing = DB::table('registration_master')
            ->where('company_id', $companyRow->id)
            ->where('user_id', $uid)
            ->where('registration_type', 'entrepreneur')
            ->orderByDesc('id')
            ->first();

        $entre = null;
        if ($existing) {
            $entre = DB::table('entrepreneur_details')
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
        $form = $this->buildStep1Form($existing, $entre, $companyRow);

        // Only flash DB → old() on clean GET (no overwrite on validation failure)
        if (!$request->session()->has('_old_input')) {
            $this->flashStep1Old($form);
        }

        // Step-2 handoff helpers for the Blade
        $step2Ready = $existing ? true : false;
        $step2Url   = $existing
            ? route('registration.entrepreneur.step2.create', [
                'company' => $companyRow->slug,
                'reg'     => (int) $existing->id,
            ])
            : '';

        return view('registration.entrepreneur.create', [
            'title'         => "{$companyRow->name} | Entrepreneur Registration — Step 1",
            'company'       => $companyRow,
            'divisions'     => $divisions,
            'professions'   => $professions,
            'businessTypes' => $businessTypes,
            'tempUploads'   => session('_temp_uploads', []),
            'ctx'           => $ctx,
            'existing'      => $existing,
            'entre'         => $entre,
            'form'          => $form,
            'step2Ready'    => $step2Ready,
            'step2Url'      => $step2Url,
            'step'          => 1,
        ]);
    }

    /**
     * Store/Update Step-1:
     * - If no existing row: insert registration_master + entrepreneur_details
     * - If existing row for this user+company: VALIDATE & UPDATE it (no side effects), then go Step-2
     * On success sets approval_status='approved', status=1 then redirects to Step-2.
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
            ->where('registration_type', 'entrepreneur')
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

            // Business Info (entrepreneur_details)
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
                if ($dupEmail) {
                    return $this->backWithInputAndTemp($request, [
                        'email' => 'This email is already registered for this company.',
                    ]);
                }
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

                    $entre = DB::table('entrepreneur_details')
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

                    if ($entre) {
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
                        'type'   => 'entrepreneur',
                        'reg_id' => $regId,
                    ]);
                } else {
                    // CREATE path
                    $regId = DB::table('registration_master')->insertGetId([
                        'company_id'        => $companyId,
                        'user_id'           => $uid,
                        'registration_type' => 'entrepreneur',
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
                            'role_id' => 12,
                            'updated_at' => now(), 
                        ]);


                    $this->logActivity('create', $uid, $companyId, [
                        'type'   => 'entrepreneur',
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
                \Log::error('Entrepreneur.store QueryException', [
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
                    '_error' => 'You already submitted Entrepreneur details for this registration.',
                ]);
            }

            return $this->backWithInputAndTemp($request, [
                '_error' => 'Failed to save. Please try again.',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Entrepreneur.store Throwable', ['err' => $e->getMessage()]);
            return $this->backWithInputAndTemp($request, [
                '_error' => 'Failed to save. Please try again.',
            ]);
        }

        session()->forget('_temp_uploads');

        // Advance to Step-2 with session flags
        session(['entrepreneur_step1_saved' => true]);

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
            ->where('registration_type', 'entrepreneur')
            ->orderByDesc('id')
            ->first();
        abort_if(!$row, 404, 'Registration not found');

        $entre = DB::table('entrepreneur_details')
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

        // prefer query step, then session flag, else 1
        $stepFromQuery = (int) $request->query('step', 0);
        $stepFromSess  = (int) session('entrepreneur.step', 1);
        $step          = $stepFromQuery > 0
            ? $stepFromQuery
            : ($stepFromSess > 0 ? $stepFromSess : 1);

        // hydration form
        $form = $this->buildStep1Form($row, $entre, $companyRow);

        // only flash DB → old() on clean GET (no overwrite on validation failure)
        if (!$request->session()->has('_old_input')) {
            $this->flashStep1Old($form);
        }

        // Step-2 helpers for Blade (when editing, Step-2 is always reachable)
        $step2Ready = true;
        $step2Url   = route('registration.entrepreneur.step2.create', [
            'company' => $companyRow->slug,
            'reg'     => (int) $row->id,
        ]);

        // keep session aligned
        session([
            'entrepreneur.reg_last' => (int) $row->id,
            'entrepreneur.step'     => $step ?: 1,
        ]);

        return view('registration.entrepreneur.create', [
            'title'         => "{$companyRow->name} | Entrepreneur Registration — Edit",
            'company'       => $companyRow,
            'row'           => $row,
            'entre'         => $entre,
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
     * New: entry-point from Public Dashboard/Header "Edit Registration" button.
     * Always opens Step-1 with existing data (latest entrepreneur reg for this user+company).
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
            ->where('registration_type', 'entrepreneur')
            ->orderByDesc('id')
            ->first();

        if (!$reg) {
            session()->forget(['entrepreneur.step', 'entrepreneur.reg_last']);

            return redirect()
                ->route('registration.entrepreneur.step1.create', [
                    'company' => $companyRow->slug,
                ])
                ->with('warning', 'No existing Entrepreneur registration found. Please start a new registration.');
        }

        // Lock context to this registration and force Step-1
        session([
            'entrepreneur.step'     => 1,
            'entrepreneur.reg_last' => (int) $reg->id,
        ]);

        // For a clean "edit entry" view, ensure we hydrate from DB (not stale validation input)
        $request->session()->forget('_old_input');

        // Reuse existing Step-1 loader which already hydrates from DB if a reg exists
        return $this->create($request, $companyRow->slug ?? $company);
    }

    /**
     * Update Step-1 fields (still single Blade).
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
            ->where('registration_type', 'entrepreneur')
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

                $entre = DB::table('entrepreneur_details')
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

                if ($entre) {
                    DB::table('entrepreneur_details')
                        ->where('company_id', $companyId)
                        ->where('registration_id', $row->id)
                        ->update($payload);
                } else {
                    $payload['created_at'] = now();
                    DB::table('entrepreneur_details')->insert($payload);
                }

                $this->logActivity('update', $uid, $companyId, [
                    'type'   => 'entrepreneur',
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
                \Log::error('Entrepreneur.update QueryException', [
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
                    '_error' => 'You already submitted Entrepreneur details for this registration.',
                ]);
            }

            return $this->backWithInputAndTemp($request, [
                '_error' => 'Failed to update. Please try again.',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Entrepreneur.update Throwable', ['err' => $e->getMessage()]);
            return $this->backWithInputAndTemp($request, [
                '_error' => 'Failed to update. Please try again.',
            ]);
        }

        session()->forget('_temp_uploads');

        // Advance to Step-2
        session(['entrepreneur_step1_saved' => true]);

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

    /* ===================== STEP-2: PRESENT JOB (single Blade) ===================== */

    public function step2Create(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        // Resolve the registration: prefer query ?reg=, else session hand-off
        $regId = (int) ($request->query('reg') ?: session('entrepreneur.reg_last', 0));

        $reg = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'entrepreneur')
            ->when($regId > 0, fn($q) => $q->where('id', $regId))
            ->orderByDesc('id')
            ->first();
        abort_if(!$reg, 404, 'Registration not found');

        $regId = (int) $reg->id;

        // Load existing present-job (if any) instead of auto-skipping to Step-3
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
                'reg'          => $regId, // keep reg id for forms if needed
            ]);
        }

        // keep session aligned for the Blade to know it's step-2
        session([
            'entrepreneur.step'     => 2,
            'entrepreneur.reg_last' => $regId,
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

        return view('registration.entrepreneur.create', [
            'title'         => "{$companyRow->name} | Entrepreneur Registration — Step 2",
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
            ?: session('entrepreneur.reg_last', 0)
        );

        $reg = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'entrepreneur')
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

        // If all empty → skip to Step-3
        $allEmpty = (
            $employer === ''
            && $jobTitle === ''
            && $department === ''
            && $joining === ''
        );
        if ($allEmpty) {
            session([
                'entrepreneur.step'     => 3,
                'entrepreneur.reg_last' => $regId,
            ]);

            return redirect()
                ->route('registration.entrepreneur.step3.create', [
                    'company' => $companyRow->slug,
                    'reg'     => $regId,
                ])
                ->with('success', 'Skipped Present Job. Proceed to Step-3.');
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
                'type'   => 'entrepreneur.step2.present_job',
                'reg_id' => $regId,
            ]);
        } catch (QueryException $e) {
            \Log::error('Entrepreneur.step2Store QueryException', [
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['_error' => 'Failed to save Present Job. Please try again.'])
                ->withInput();
        } catch (\Throwable $e) {
            \Log::error('Entrepreneur.step2Store Throwable', ['err' => $e->getMessage()]);

            return back()
                ->withErrors(['_error' => 'Failed to save Present Job. Please try again.'])
                ->withInput();
        }

        // Success → Step-3
        session([
            'entrepreneur.step'     => 3,
            'entrepreneur.reg_last' => $regId,
        ]);

        return redirect()
            ->route('registration.entrepreneur.step3.create', [
                'company' => $companyRow->slug,
                'reg'     => $regId,
            ])
            ->with('success', 'Present Job saved. Proceed to Step-3.');
    }

    /* ===================== STEP-3: EDUCATION (single Blade) ===================== */

    public function step3Create(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        $regId = (int) ($request->query('reg') ?: session('entrepreneur.reg_last', 0));
        $reg   = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'entrepreneur')
            ->when($regId > 0, fn($q) => $q->where('id', $regId))
            ->orderByDesc('id')
            ->first();
        abort_if(!$reg, 404, 'Registration not found');

        $regId = (int) $reg->id;

        // Common lists/context
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

        // STEP-3 specific payloads
        $degrees = DB::table('degrees')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name as degree_name']);

        $education = DB::table('education_background')
            ->where('Company_id', $companyId)
            ->where('registration_id', $regId)
            ->orderBy('id')
            ->get([
                'id',
                'degree_id',
                'Institution as institution',
                'Passing_Year as passing_year',
                'Result_Type as result_type',
                'obtained_grade_or_score',
                'Out_of as out_of',
            ]);

        session([
            'entrepreneur.step'     => 3,
            'entrepreneur.reg_last' => $regId,
        ]);

        return view('registration.entrepreneur.create', [
            'title'         => "{$companyRow->name} | Entrepreneur Registration — Step 3",
            'company'       => $companyRow,
            'divisions'     => $divisions,
            'professions'   => $professions,
            'businessTypes' => $businessTypes,
            'tempUploads'   => session('_temp_uploads', []),
            'ctx'           => $ctx,
            'existing'      => $reg,
            'reg'           => $reg,
            'step'          => 3,
            'degrees'       => $degrees,
            'education'     => $education,
        ]);
    }

    public function step3Store(Request $request, $company)
    {
        // Delegates to the Step-3 implementation (validation + DB upsert + navigation).
        return $this->step3StoreImpl($request, $company);
    }

    /* ===================== STEP-2 WRAPPERS (aliases) ===================== */

    public function step2Next(Request $request, $company)
    {
        return $this->step2Store($request, $company);
    }

    /* ===================== STEP-3 SUPPORT: HELPERS + IMPLEMENTATION ===================== */

    /** Allowed enums for education_background.Result_Type */
    private function eduResultTypes(): array
    {
        return ['GPA', 'CGPA', 'Division', 'Class', 'Percentage'];
    }

    private function normalizeEducationRecords(Request $request): array
    {
        $rows = $request->input('education', []);
        if (!is_array($rows)) {
            return [];
        }

        $clean = [];
        foreach ($rows as $i => $r) {
            if (!is_array($r)) {
                continue;
            }

            $degreeId = isset($r['degree_id']) ? (int) $r['degree_id'] : null;
            $inst     = isset($r['institution']) ? trim((string) $r['institution']) : '';

            $pyearRaw = $r['passing_year'] ?? null;
            $pyear    = ($pyearRaw === '' || $pyearRaw === null) ? null : (int) $pyearRaw;

            $rtypeRaw = $r['result_type'] ?? null;
            $rtype    = ($rtypeRaw === '' || $rtypeRaw === null) ? null : (string) $rtypeRaw;

            $scoreRaw = $r['obtained_grade_or_score'] ?? null;
            $score    = ($scoreRaw === '' || $scoreRaw === null) ? null : trim((string) $scoreRaw);

            $outOfRaw = $r['out_of'] ?? null;
            $outOf    = ($outOfRaw === '' || $outOfRaw === null) ? null : (int) $outOfRaw;

            $clean[] = [
                'degree_id'               => $degreeId,
                'institution'             => $inst,
                'passing_year'            => $pyear,
                'result_type'             => $rtype,
                'obtained_grade_or_score' => $score,
                'out_of'                  => $outOf,
                '_row'                    => $i, // for error pointers
            ];
        }

        return $clean;
    }

    private function validateEducation(array $rows): array
    {
        $errors = [];
        $nowY   = (int) date('Y');
        $enums  = $this->eduResultTypes();

        if (count($rows) === 0) {
            $errors['education'] = 'At least one education record is required.';
            return ['ok' => false, 'errors' => $errors, 'data' => []];
        }

        foreach ($rows as $idx => $r) {
            $ptr = "education.{$idx}";

            if (empty($r['degree_id'])) {
                $errors["{$ptr}.degree_id"] = 'Select a degree.';
            }
            if ($r['institution'] === '') {
                $errors["{$ptr}.institution"] = 'Enter institution name.';
            }

            if ($r['passing_year'] !== null) {
                $py = (int) $r['passing_year'];
                if ($py < 1950 || $py > $nowY) {
                    $errors["{$ptr}.passing_year"] = "Passing year must be between 1950 and {$nowY}.";
                }

                if (empty($r['result_type']) || !in_array($r['result_type'], $enums, true)) {
                    $errors["{$ptr}.result_type"] = 'Select a valid result type.';
                }

                if ($r['obtained_grade_or_score'] === null || $r['obtained_grade_or_score'] === '') {
                    $errors["{$ptr}.obtained_grade_or_score"] = 'Enter obtained grade/score.';
                } elseif (mb_strlen((string) $r['obtained_grade_or_score']) > 20) {
                    $errors["{$ptr}.obtained_grade_or_score"] = 'Grade/score must be ≤ 20 chars.';
                }

                if ($r['out_of'] === null) {
                    $errors["{$ptr}.out_of"] = 'Enter "out of" value.';
                } else {
                    $oo = (int) $r['out_of'];
                    if ($oo <= 0 || $oo > 1000) {
                        $errors["{$ptr}.out_of"] = 'Out of must be a positive integer (≤ 1000).';
                    }
                }
            } else {
                // Under-study: result triad must be empty
                if (
                    !empty($r['result_type'])
                    || !empty($r['obtained_grade_or_score'])
                    || !empty($r['out_of'])
                ) {
                    $errors["{$ptr}.result_type"] = 'For under-study, leave result fields empty.';
                }
            }
        }

        return [
            'ok'     => empty($errors),
            'errors' => $errors,
            'data'   => $rows,
        ];
    }

    private function upsertEducation(int $companyId, int $regId, int $uid, array $rows): void
    {
        DB::table('education_background')
            ->where('Company_id', $companyId)
            ->where('registration_id', $regId)
            ->delete();

        $now   = now();
        $batch = [];

        foreach ($rows as $r) {
            $batch[] = [
                'Company_id'              => $companyId,
                'registration_id'         => $regId,
                'degree_id'               => (int) $r['degree_id'],
                'Institution'             => $r['institution'],
                'Passing_Year'            => $r['passing_year'],            // nullable
                'Result_Type'             => $r['result_type'],             // nullable
                'obtained_grade_or_score' => $r['obtained_grade_or_score'], // nullable
                'Out_of'                  => $r['out_of'],                  // nullable
                'status'                  => 1,
                'created_by'              => $uid,
                'updated_by'              => $uid,
                'created_at'              => $now,
                'updated_at'              => $now,
            ];
        }

        if (!empty($batch)) {
            DB::table('education_background')->insert($batch);
        }
    }

    public function step3StoreImpl(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        $regId = (int) (
            $request->input('reg')
            ?: $request->query('reg')
            ?: session('entrepreneur.reg_last', 0)
        );

        $reg = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'entrepreneur')
            ->when($regId > 0, fn($q) => $q->where('id', $regId))
            ->orderByDesc('id')
            ->first();
        abort_if(!$reg, 404, 'Registration not found');

        $regId = (int) $reg->id;

        $rows   = $this->normalizeEducationRecords($request);
        $result = $this->validateEducation($rows);

        if (!$result['ok']) {
            session([
                'entrepreneur.step'     => 3,
                'entrepreneur.reg_last' => $regId,
            ]);

            return back()
                ->withErrors($result['errors'])
                ->withInput();
        }

        try {
            DB::transaction(function () use ($companyId, $regId, $uid, $result) {
                $this->upsertEducation($companyId, $regId, $uid, $result['data']);
            });

            $this->logActivity('upsert', $uid, $companyId, [
                'type'   => 'entrepreneur.step3.education',
                'reg_id' => $regId,
                'count'  => count($result['data']),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Entrepreneur.step3 upsert failed', ['err' => $e->getMessage()]);

            session([
                'entrepreneur.step'     => 3,
                'entrepreneur.reg_last' => $regId,
            ]);

            return back()
                ->withErrors(['_error' => 'Failed to save Education. Please try again.'])
                ->withInput();
        }

        // tolerate both 'nav' and 'action' coming from the Blade
        $navRaw    = strtolower((string) $request->input('nav', ''));
        $actionRaw = strtolower((string) $request->input('action', ''));
        $nav       = $navRaw !== '' ? $navRaw : ($actionRaw !== '' ? $actionRaw : 'next'); // 'back' | 'save' | 'next'

        if ($nav === 'back') {
            session([
                'entrepreneur.step'     => 2,
                'entrepreneur.reg_last' => $regId,
            ]);

            return redirect()
                ->route('registration.entrepreneur.step2.create', [
                    'company' => $companyRow->slug,
                    'reg'     => $regId,
                ])
                ->with('success', 'Education saved. You can review Step-2.');
        }

        session([
            'entrepreneur.step'     => 4,
            'entrepreneur.reg_last' => $regId,
        ]);

        return redirect()
            ->route('registration.entrepreneur.step4.create', [
                'company' => $companyRow->slug,
                'reg'     => $regId,
            ])
            ->with('success', 'Education saved. Proceed to Step-4.');
    }

    /* ===================== LOOKUPS (for degrees / tasks / trainings) ===================== */

    public function degrees(Request $request, $company)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = DB::table('degrees')
            ->where('is_active', 1)
            ->when($q !== '', fn($qr) => $qr->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->get(['id', 'name as degree_name']);

        return response()->json($rows);
    }

    public function tasks(Request $request, $company)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = DB::table('tasks_param')
            ->where('status', 1)
            ->when(
                $q !== '',
                fn($qr) => $qr->where('Task_Param_Name', 'like', "%{$q}%")
            )
            ->orderBy('Task_Param_Name')
            ->get([
                'Task_Param_ID as id',
                'Task_Param_Name as task_param_name',
            ]);

        return response()->json($rows);
    }

    public function trainingsByCategory(Request $request, $company)
    {
        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        $catId = (int) $request->query('category_id', 0);
        abort_if($catId <= 0, 400, 'category_id is required');

        $rows = DB::table('training_list')
            ->where('status', 1)
            ->where('Company_id', $companyId)
            ->where('Training_Category_Id', $catId)
            ->orderBy('Training_Name')
            ->get([
                'Training_ID as id',
                'Training_Name as training_name',
            ]);

        return response()->json($rows);
    }

    /* ===================== STEP-4: SOFTWARE & SKILLS ===================== */

    public function step4Create(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        // Resolve registration (prefer ?reg, else session)
        $regId = (int) ($request->query('reg') ?: session('entrepreneur.reg_last', 0));

        $reg = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'entrepreneur')
            ->when($regId > 0, fn($q) => $q->where('id', $regId))
            ->orderByDesc('id')
            ->first();
        abort_if(!$reg, 404, 'Registration not found');

        $regId = (int) $reg->id;

        // Lists (status=1)
        $softwareList = DB::table('software_list')
            ->where('status', 1)
            ->orderBy('software_name')
            ->get(['id', 'software_name']);

        $skillList = DB::table('skills')
            ->where('status', 1)
            ->orderBy('skill')
            ->get(['id', 'skill']);

        // Existing selections
        $expertRows = DB::table('expertise_on_softwares')
            ->where('company_id', $companyId)
            ->where('registration_id', $regId)
            ->get(['expert_on_software as id', 'experience_in_years']);

        $selectedSoftware = [];
        foreach ($expertRows as $r) {
            $selectedSoftware[(int) $r->id] = (float) ($r->experience_in_years ?? 0);
        }

        $skillRows = DB::table('person_skills')
            ->where('company_id', $companyId)
            ->where('registration_id', $regId)
            ->get(['skill']);

        $selectedSkills = $skillRows
            ->pluck('skill')
            ->map(fn($v) => (int) $v)
            ->all();

        // Normalized helper arrays for JS/TomSelect
        $selectedSoftwareIds = array_keys($selectedSoftware);
        $softwareYears       = $selectedSoftware;
        $selectedSkillIds    = $selectedSkills;

        // Common context
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

        session([
            'entrepreneur.step'     => 4,
            'entrepreneur.reg_last' => $regId,
        ]);

        return view('registration.entrepreneur.create', [
            'title'               => "{$companyRow->name} | Entrepreneur Registration — Step 4",
            'company'             => $companyRow,
            'divisions'           => $divisions,
            'professions'         => $professions,
            'businessTypes'       => $businessTypes,
            'tempUploads'         => session('_temp_uploads', []),
            'ctx'                 => $ctx,
            'existing'            => $reg,
            'reg'                 => $reg,
            'step'                => 4,

            // master lists
            'softwareList'        => $softwareList,
            'skillList'           => $skillList,

            // aliases for Blade convenience
            'software'            => $softwareList,
            'skills'              => $skillList,

            // selections
            'selectedSoftware'    => $selectedSoftware,    // [software_id => years]
            'selectedSoftwareIds' => $selectedSoftwareIds, // [software_id, ...]
            'softwareYears'       => $softwareYears,       // [software_id => years]
            'selectedSkills'      => $selectedSkills,      // [skill_id, ...]
            'selectedSkillIds'    => $selectedSkillIds,    // [skill_id, ...]
        ]);
    }

    public function step4Store(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        // Resolve reg strictly for this user+company
        $regId = (int) (
            $request->input('reg')
            ?: $request->query('reg')
            ?: session('entrepreneur.reg_last', 0)
        );

        $reg = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'entrepreneur')
            ->when($regId > 0, fn($q) => $q->where('id', $regId))
            ->orderByDesc('id')
            ->first();
        abort_if(!$reg, 404, 'Registration not found');

        $regId = (int) $reg->id;

        // ================= SOFTWARE IDS (flexible fallback) =================
        $softwareIdsRaw = $request->input('software_ids', []);
        if (!is_array($softwareIdsRaw) || empty($softwareIdsRaw)) {
            $softwareIdsRaw = $request->input('expert_on_software', []);
        }
        if (!is_array($softwareIdsRaw) || empty($softwareIdsRaw)) {
            $softwareIdsRaw = $request->input('software', []);
        }
        if (!is_array($softwareIdsRaw) || empty($softwareIdsRaw)) {
            $softwareIdsRaw = $request->input('softwares', []);
        }

        $softwareIds = collect($softwareIdsRaw)
            ->map(fn($v) => (int) $v)
            ->filter()
            ->unique()
            ->values()
            ->all();

        // years map could come as years[ID] or yearsMap[ID]
        $yearsMapRaw = $request->input('years', []);
        if (!is_array($yearsMapRaw) || empty($yearsMapRaw)) {
            $yearsMapRaw = $request->input('yearsMap', []);
        }

        $yearsMap = [];
        foreach ($yearsMapRaw as $k => $v) {
            $id            = (int) $k;
            $yearsMap[$id] = is_numeric($v) ? (float) $v : null;
        }

        // ================= SKILL IDS (already flexible) =====================
        $skillIds = collect($request->input('skill_ids', []))
            ->when(
                empty($request->input('skill_ids')),
                fn($c) => collect($request->input('skills', []))
            )
            ->map(fn($v) => (int) $v)
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Validation:
        $errors = [];

        if (!empty($softwareIds)) {
            $validSoft = DB::table('software_list')
                ->whereIn('id', $softwareIds)
                ->where('status', 1)
                ->pluck('id')
                ->map(fn($v) => (int) $v)
                ->all();

            $missing = array_values(array_diff($softwareIds, $validSoft));
            if (!empty($missing)) {
                $errors['software_ids'] = 'One or more selected software items are invalid.';
            }

            foreach ($softwareIds as $sid) {
                $yrs = $yearsMap[$sid] ?? null;
                if ($yrs === null || !is_numeric($yrs) || $yrs <= 0) {
                    $errors["years.$sid"] =
                        'Enter valid experience in years (e.g., 0.5, 1, 2.5) for each selected software.';
                } elseif ($yrs > 60) {
                    $errors["years.$sid"] =
                        'Experience seems unrealistic. Please enter <= 60 years.';
                }
            }
        }

        if (!empty($skillIds)) {
            $validSkills = DB::table('skills')
                ->whereIn('id', $skillIds)
                ->where('status', 1)
                ->pluck('id')
                ->map(fn($v) => (int) $v)
                ->all();

            $missingS = array_values(array_diff($skillIds, $validSkills));
            if (!empty($missingS)) {
                $errors['skill_ids'] = 'One or more selected skills are invalid.';
            }
        }

        if (!empty($errors)) {
            return back()
                ->withErrors($errors)
                ->withInput();
        }

        // Persist
        try {
            DB::transaction(function () use (
                $uid,
                $companyId,
                $regId,
                $softwareIds,
                $yearsMap,
                $skillIds
            ) {
                // SOFTWARE
                $currentSoft = DB::table('expertise_on_softwares')
                    ->where('company_id', $companyId)
                    ->where('registration_id', $regId)
                    ->pluck('expert_on_software')
                    ->map(fn($v) => (int) $v)
                    ->all();

                if (!empty($currentSoft)) {
                    $toDelete = array_values(array_diff($currentSoft, $softwareIds));
                    if (!empty($toDelete)) {
                        DB::table('expertise_on_softwares')
                            ->where('company_id', $companyId)
                            ->where('registration_id', $regId)
                            ->whereIn('expert_on_software', $toDelete)
                            ->delete();
                    }
                }

                foreach ($softwareIds as $sid) {
                    $yrs    = (float) ($yearsMap[$sid] ?? 0);
                    $exists = DB::table('expertise_on_softwares')
                        ->where('company_id', $companyId)
                        ->where('registration_id', $regId)
                        ->where('expert_on_software', $sid)
                        ->exists();

                    if ($exists) {
                        DB::table('expertise_on_softwares')
                            ->where('company_id', $companyId)
                            ->where('registration_id', $regId)
                            ->where('expert_on_software', $sid)
                            ->update([
                                'experience_in_years' => $yrs,
                                'updated_by'          => $uid,
                                'updated_at'          => now(),
                            ]);
                    } else {
                        DB::table('expertise_on_softwares')->insert([
                            'company_id'          => $companyId,
                            'registration_id'     => $regId,
                            'expert_on_software'  => $sid,
                            'experience_in_years' => $yrs,
                            'status'              => 1,
                            'created_by'          => $uid,
                            'updated_by'          => $uid,
                            'created_at'          => now(),
                            'updated_at'          => now(),
                        ]);
                    }
                }

                if (empty($softwareIds)) {
                    DB::table('expertise_on_softwares')
                        ->where('company_id', $companyId)
                        ->where('registration_id', $regId)
                        ->delete();
                }

                // SKILLS
                $currentSkills = DB::table('person_skills')
                    ->where('company_id', $companyId)
                    ->where('registration_id', $regId)
                    ->pluck('skill')
                    ->map(fn($v) => (int) $v)
                    ->all();

                if (!empty($currentSkills)) {
                    $toDeleteS = array_values(array_diff($currentSkills, $skillIds));
                    if (!empty($toDeleteS)) {
                        DB::table('person_skills')
                            ->where('company_id', $companyId)
                            ->where('registration_id', $regId)
                            ->whereIn('skill', $toDeleteS)
                            ->delete();
                    }
                }

                $toInsertS = array_values(array_diff($skillIds, $currentSkills));
                foreach ($toInsertS as $sk) {
                    DB::table('person_skills')->insert([
                        'company_id'      => $companyId,
                        'registration_id' => $regId,
                        'skill'           => (int) $sk,
                        'status'          => 1,
                        'created_by'      => $uid,
                        'updated_by'      => $uid,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }

                if (empty($skillIds)) {
                    DB::table('person_skills')
                        ->where('company_id', $companyId)
                        ->where('registration_id', $regId)
                        ->delete();
                }
            });

            $this->logActivity('upsert', $uid, $companyId, [
                'type'   => 'entrepreneur.step4',
                'reg_id' => $regId,
            ]);
        } catch (QueryException $e) {
            \Log::error('Entrepreneur.step4Store QueryException', [
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['_error' => 'Failed to save Software/Skills. Please try again.'])
                ->withInput();
        } catch (\Throwable $e) {
            \Log::error('Entrepreneur.step4Store Throwable', ['err' => $e->getMessage()]);

            return back()
                ->withErrors(['_error' => 'Failed to save Software/Skills. Please try again.'])
                ->withInput();
        }

        // Navigation
        $action = (string) $request->input('action', 'next'); // 'back' | 'save' | 'next'

        session(['entrepreneur.reg_last' => $regId]);

        if ($action === 'back') {
            session(['entrepreneur.step' => 3]);

            return redirect()
                ->route('registration.entrepreneur.step3.create', [
                    'company' => $companyRow->slug,
                    'reg'     => $regId,
                ])
                ->with('success', 'Saved. Back to Step-3.');
        }

        if ($action === 'save') {
            session(['entrepreneur.step' => 4]);

            return redirect()
                ->route('registration.entrepreneur.step4.create', [
                    'company' => $companyRow->slug,
                    'reg'     => $regId,
                ])
                ->with('success', 'Saved Software & Skills.');
        }

        session(['entrepreneur.step' => 5]);

        return redirect()
            ->route('registration.entrepreneur.step5.create', [
                'company' => $companyRow->slug,
                'reg'     => $regId,
            ])
            ->with('success', 'Saved. Proceed to Step-5.');
    }

    /* ===================== OPTIONAL LOOKUPS (AJAX) ===================== */

    public function software(Request $request, $company)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = DB::table('software_list')
            ->where('status', 1)
            ->when(
                $q !== '',
                fn($qq) => $qq->where('software_name', 'like', "%{$q}%")
            )
            ->orderBy('software_name')
            ->limit(100)
            ->get(['id', 'software_name']);

        return response()->json($rows);
    }

    public function skills(Request $request, $company)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = DB::table('skills')
            ->where('status', 1)
            ->when(
                $q !== '',
                fn($qq) => $qq->where('skill', 'like', "%{$q}%")
            )
            ->orderBy('skill')
            ->limit(100)
            ->get(['id', 'skill'])
            ->values();

        return response()->json($rows);
    }

    /* ===================== STEP-5: AREA OF INTEREST (Preferred Areas) ===================== */

    private function normalizePreferredAreas(Request $request): array
    {
        $ids = $request->input('task_ids', []);
        if (!is_array($ids) || empty($ids)) {
            // Fallback if Blade posts "tasks[]" instead of "task_ids[]"
            $ids = $request->input('tasks', []);
        }
        if (!is_array($ids)) {
            return [];
        }

        return collect($ids)
            ->map(fn($v) => (int) $v)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function upsertPreferredAreas(int $companyId, int $regId, int $uid, array $taskIds): void
    {
        DB::table('preffered_area_of_job')
            ->where('Company_id', $companyId)
            ->where('registration_id', $regId)
            ->delete();

        if (empty($taskIds)) {
            return;
        }

        $now   = now();
        $batch = [];

        foreach ($taskIds as $tid) {
            $batch[] = [
                'Company_id'      => $companyId,
                'registration_id' => $regId,
                'Task_Param_ID'   => (int) $tid,
                'status'          => 1,
                'created_by'      => $uid,
                'updated_by'      => $uid,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        DB::table('preffered_area_of_job')->insert($batch);
    }

    public function step5Create(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        // Resolve registration (prefer ?reg, else session)
        $regId = (int) ($request->query('reg') ?: session('entrepreneur.reg_last', 0));

        $reg = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'entrepreneur')
            ->when($regId > 0, fn($q) => $q->where('id', $regId))
            ->orderByDesc('id')
            ->first();
        abort_if(!$reg, 404, 'Registration not found');

        $regId = (int) $reg->id;

        // Lists for the single Blade
        $tasks = DB::table('tasks_param')
            ->where('status', 1)
            ->orderBy('Task_Param_Name')
            ->get([
                'Task_Param_ID as id',
                'Task_Param_Name as task_param_name',
            ]);

        // Existing selections
        $selectedTasks = DB::table('preffered_area_of_job')
            ->where('Company_id', $companyId)
            ->where('registration_id', $regId)
            ->pluck('Task_Param_ID')
            ->map(fn($v) => (int) $v)
            ->all();

        $selectedTaskIds = $selectedTasks;

        // Common context
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

        session([
            'entrepreneur.step'     => 5,
            'entrepreneur.reg_last' => $regId,
        ]);

        return view('registration.entrepreneur.create', [
            'title'           => "{$companyRow->name} | Entrepreneur Registration — Step 5",
            'company'         => $companyRow,
            'divisions'       => $divisions,
            'professions'     => $professions,
            'businessTypes'   => $businessTypes,
            'tempUploads'     => session('_temp_uploads', []),
            'ctx'             => $ctx,
            'existing'        => $reg,
            'reg'             => $reg,
            'step'            => 5,

            // main list
            'tasks'           => $tasks,

            // alias + selections (for Blade compatibility)
            'taskList'        => $tasks,
            'taskParams'      => $tasks,        // alias to fix $taskParams undefined
            'selectedTasks'   => $selectedTasks,   // [task_id, ...]
            'selectedTaskIds' => $selectedTaskIds, // [task_id, ...]
        ]);
    }

    public function step5Store(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        // Resolve reg
        $regId = (int) (
            $request->input('reg')
            ?: $request->query('reg')
            ?: session('entrepreneur.reg_last', 0)
        );

        $reg = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'entrepreneur')
            ->when($regId > 0, fn($q) => $q->where('id', $regId))
            ->orderByDesc('id')
            ->first();
        abort_if(!$reg, 404, 'Registration not found');

        $regId = (int) $reg->id;

        // Normalize
        $taskIds = $this->normalizePreferredAreas($request);

        // Validate IDs exist (optional feature, but if provided, must be valid)
        $errors = [];
        if (!empty($taskIds)) {
            $validTasks = DB::table('tasks_param')
                ->where('status', 1)
                ->whereIn('Task_Param_ID', $taskIds)
                ->pluck('Task_Param_ID')
                ->map(fn($v) => (int) $v)
                ->all();

            $missing = array_values(array_diff($taskIds, $validTasks));
            if (!empty($missing)) {
                $errors['task_ids'] = 'One or more preferred areas are invalid.';
            }
        }

        if (!empty($errors)) {
            session([
                'entrepreneur.step'     => 5,
                'entrepreneur.reg_last' => $regId,
            ]);

            return back()
                ->withErrors($errors)
                ->withInput();
        }

        // Persist (replace-all)
        try {
            DB::transaction(function () use ($companyId, $regId, $uid, $taskIds) {
                $this->upsertPreferredAreas($companyId, $regId, $uid, $taskIds);
            });

            $this->logActivity('upsert', $uid, $companyId, [
                'type'   => 'entrepreneur.step5',
                'reg_id' => $regId,
                'pa_cnt' => count($taskIds),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Entrepreneur.step5Store Throwable', ['err' => $e->getMessage()]);

            session([
                'entrepreneur.step'     => 5,
                'entrepreneur.reg_last' => $regId,
            ]);

            return back()
                ->withErrors(['_error' => 'Failed to save Preferred Areas. Please try again.'])
                ->withInput();
        }

        // Navigation: back→Step-4, save→stay, next→Step-6
        $action = (string) $request->input('action', 'next'); // 'back' | 'save' | 'next'

        session(['entrepreneur.reg_last' => $regId]);

        if ($action === 'back') {
            session(['entrepreneur.step' => 4]);

            return redirect()
                ->route('registration.entrepreneur.step4.create', [
                    'company' => $companyRow->slug,
                    'reg'     => $regId,
                ])
                ->with('success', 'Saved. Back to Step-4.');
        }

        if ($action === 'save') {
            session(['entrepreneur.step' => 5]);

            return redirect()
                ->route('registration.entrepreneur.step5.create', [
                    'company' => $companyRow->slug,
                    'reg'     => $regId,
                ])
                ->with('success', 'Saved Preferred Areas.');
        }

        session(['entrepreneur.step' => 6]);

        return redirect()
            ->route('registration.entrepreneur.step6.create', [
                'company' => $companyRow->slug,
                'reg'     => $regId,
            ])
            ->with('success', 'Saved. Proceed to Step-6.');
    }

    /* ===================== STEP-6: TRAINING REQUIRED ===================== */

    private function normalizeTrainings(Request $request): array
    {
        // NEW: primary key from Blade (train[index][category_id/training_id])
        $rows = $request->input('train', []);

        // Backward compatibility: fall back to legacy "trainings" if present
        if (!is_array($rows) || empty($rows)) {
            $rows = $request->input('trainings', []);
        }

        if (!is_array($rows)) {
            return [];
        }

        $clean = [];
        foreach ($rows as $r) {
            if (!is_array($r)) {
                continue;
            }

            $cid = isset($r['category_id']) ? (int) $r['category_id'] : 0;
            $tid = isset($r['training_id']) ? (int) $r['training_id'] : 0;

            if ($cid > 0 && $tid > 0) {
                $clean[] = [
                    'category_id' => $cid,
                    'training_id' => $tid,
                ];
            }
        }

        // De-duplicate category+training pairs
        $seen = [];
        $uniq = [];
        foreach ($clean as $it) {
            $key = $it['category_id'] . '-' . $it['training_id'];
            if (!isset($seen[$key])) {
                $seen[$key] = 1;
                $uniq[]     = $it;
            }
        }

        return $uniq;
    }

    private function upsertTrainings(int $companyId, int $regId, int $uid, array $items): void
    {
        DB::table('training_required')
            ->where('Company_id', $companyId)
            ->where('registration_id', $regId)
            ->delete();

        if (empty($items)) {
            return;
        }

        $now   = now();
        $batch = [];

        foreach ($items as $it) {
            $batch[] = [
                'Company_id'           => $companyId,
                'registration_id'      => $regId,
                'Training_Category_Id' => (int) $it['category_id'],
                'Training_ID'          => (int) $it['training_id'],
                'status'               => 1,
                'created_by'           => $uid,
                'updated_by'           => $uid,
                'created_at'           => $now,
                'updated_at'           => $now,
            ];
        }

        DB::table('training_required')->insert($batch);
    }

    public function step6Create(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        // Resolve registration (prefer ?reg, else session)
        $regId = (int) ($request->query('reg') ?: session('entrepreneur.reg_last', 0));

        $reg = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'entrepreneur')
            ->when($regId > 0, fn($q) => $q->where('id', $regId))
            ->orderByDesc('id')
            ->first();
        abort_if(!$reg, 404, 'Registration not found');

        $regId = (int) $reg->id;

        // ===== Lists for Step-6 =====

        // Training categories (per company)
        $trainingCats = DB::table('training_category')
            ->where('status', 1)
            ->where('Company_id', $companyId)
            ->orderBy('Training_Category_Name')
            ->get([
                'Training_Category_Id as id',
                'Training_Category_Name as category_name',
            ]);

        // Full training list for this company (used by JS/TomSelect map)
        $trainingList = DB::table('training_list')
            ->where('status', 1)
            ->where('Company_id', $companyId)
            ->orderBy('Training_Name')
            ->get([
                'Training_Category_Id as category_id',
                'Training_ID as id',
                'Training_Name as training_name',
            ]);

        // Existing selections
        $selectedTrainings = DB::table('training_required')
            ->where('Company_id', $companyId)
            ->where('registration_id', $regId)
            ->get([
                'Training_Category_Id as category_id',
                'Training_ID as training_id',
            ]);

        // Normalized pairs for JS
        $selectedTrainingPairs = $selectedTrainings
            ->map(fn($r) => [
                'category_id' => (int) $r->category_id,
                'training_id' => (int) $r->training_id,
            ])
            ->values();

        // Controller-supplied array for Blade's $trainingRows contract
        $trainingRows = $selectedTrainingPairs->all();

        // Common context
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

        session([
            'entrepreneur.step'     => 6,
            'entrepreneur.reg_last' => $regId,
        ]);

        return view('registration.entrepreneur.create', [
            'title'                 => "{$companyRow->name} | Entrepreneur Registration — Step 6",
            'company'               => $companyRow,
            'divisions'             => $divisions,
            'professions'           => $professions,
            'businessTypes'         => $businessTypes,
            'tempUploads'           => session('_temp_uploads', []),
            'ctx'                   => $ctx,
            'existing'              => $reg,
            'reg'                   => $reg,
            'step'                  => 6,

            // category list
            'trainingCats'          => $trainingCats,
            'trainingCategories'    => $trainingCats,       // alias for Blade

            // full training list for JS/TomSelect
            'trainingList'          => $trainingList,
            'allTrainings'          => $trainingList,       // alias for Blade/JS

            // pre-selected pairs
            'selectedTrainings'     => $selectedTrainings,     // raw rows (if Blade uses it)
            'selectedTrainingPairs' => $selectedTrainingPairs, // normalized pairs for JS
            'trainingRows'          => $trainingRows,          // normalized array for Blade's v6.4.1 contract
        ]);
    }

    public function step6Store(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        // Resolve reg
        $regId = (int) (
            $request->input('reg')
            ?: $request->query('reg')
            ?: session('entrepreneur.reg_last', 0)
        );

        $reg = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'entrepreneur')
            ->when($regId > 0, fn($q) => $q->where('id', $regId))
            ->orderByDesc('id')
            ->first();
        abort_if(!$reg, 404, 'Registration not found');

        $regId = (int) $reg->id;

        // Navigation: Step-6 has no "Next". Support 'back' to Step-5 or 'save' to stay.
        $action = strtolower((string) $request->input('action', 'save')); // 'back' | 'save'

        // Normalize
        $trainings = $this->normalizeTrainings($request);

        // If user pressed Back with an empty payload, do not wipe existing trainings.
        if ($action === 'back' && empty($trainings)) {
            session([
                'entrepreneur.step'     => 5,
                'entrepreneur.reg_last' => $regId,
            ]);

            return redirect()
                ->route('registration.entrepreneur.step5.create', [
                    'company' => $companyRow->slug,
                    'reg'     => $regId,
                ])
                ->with('success', 'Back to Step-5.');
        }

        // Validate IDs exist (optional, but if provided, must be valid for this company)
        $errors = [];
        if (!empty($trainings)) {
            $catIds = array_values(
                array_unique(
                    array_map(fn($x) => (int) $x['category_id'], $trainings)
                )
            );

            $validCats = DB::table('training_category')
                ->where('status', 1)
                ->where('Company_id', $companyId)
                ->whereIn('Training_Category_Id', $catIds)
                ->pluck('Training_Category_Id')
                ->map(fn($v) => (int) $v)
                ->all();

            $missingC = array_values(array_diff($catIds, $validCats));
            if (!empty($missingC)) {
                $errors['trainings.category'] =
                    'One or more training categories are invalid for this company.';
            }

            foreach ($trainings as $i => $it) {
                $ok = DB::table('training_list')
                    ->where('status', 1)
                    ->where('Company_id', $companyId)
                    ->where('Training_Category_Id', (int) $it['category_id'])
                    ->where('Training_ID', (int) $it['training_id'])
                    ->exists();

                if (!$ok) {
                    $errors["trainings.$i"] =
                        'Invalid training item or category mismatch for this company.';
                }
            }
        }

        if (!empty($errors)) {
            session([
                'entrepreneur.step'     => 6,
                'entrepreneur.reg_last' => $regId,
            ]);

            return back()
                ->withErrors($errors)
                ->withInput();
        }

        // Persist (replace-all)
        try {
            DB::transaction(function () use ($companyId, $regId, $uid, $trainings) {
                $this->upsertTrainings($companyId, $regId, $uid, $trainings);
            });

            $this->logActivity('upsert', $uid, $companyId, [
                'type'   => 'entrepreneur.step6',
                'reg_id' => $regId,
                'tr_cnt' => count($trainings),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Entrepreneur.step6Store Throwable', ['err' => $e->getMessage()]);

            session([
                'entrepreneur.step'     => 6,
                'entrepreneur.reg_last' => $regId,
            ]);

            return back()
                ->withErrors(['_error' => 'Failed to save Training Required. Please try again.'])
                ->withInput();
        }

        session(['entrepreneur.reg_last' => $regId]);

        if ($action === 'back') {
            session(['entrepreneur.step' => 5]);

            return redirect()
                ->route('registration.entrepreneur.step5.create', [
                    'company' => $companyRow->slug,
                    'reg'     => $regId,
                ])
                ->with('success', 'Saved. Back to Step-5.');
        }

        session([
            'entrepreneur.step'      => 6,
            'entrepreneur.completed' => true,
        ]);

        return redirect()
            ->route('registration.entrepreneur.step6.create', [
                'company' => $companyRow->slug,
                'reg'     => $regId,
            ])
            ->with('success', 'Congratulations! You have successfully completed all steps. Head Office will deliver your Entrepreneur card as appropriate.');
    }
}
