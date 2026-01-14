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
use Carbon\Carbon;

class CompanyOfficerController extends Controller
{
    /* ======== UTIL ======== */

    private function currentUserId(): ?int
    {
        $forced = config('header.dev_force_user_id');

        // If dev forced user id is set, ALWAYS use it for this module.
        if (is_numeric($forced)) {
            return (int) $forced;
        }

        return Auth::id();
    }

    /** Resolve company by {company} slug or from user/role fallback */
    private function resolveCompany($routeCompany): ?object
    {
        if ($routeCompany instanceof \App\Models\SuperAdmin\GlobalSetup\Company) {
            return DB::table('companies')
                ->where('id', $routeCompany->id)->where('status',1)->whereNull('deleted_at')->first();
        }
        if (is_string($routeCompany) && $routeCompany !== '') {
            return DB::table('companies')
                ->where('slug',$routeCompany)->where('status',1)->whereNull('deleted_at')->first();
        }

        $uid = $this->currentUserId();
        if ($uid) {
            $user = DB::table('users')->where('id',$uid)->first();
            if ($user && $user->company_id) {
                return DB::table('companies')
                    ->where('id',$user->company_id)->where('status',1)->whereNull('deleted_at')->first();
            }
            if ($user && $user->role_id) {
                $roleCompanyId = DB::table('roles')->where('id',$user->role_id)->value('company_id');
                if ($roleCompanyId) {
                    return DB::table('companies')
                        ->where('id',$roleCompanyId)->where('status',1)->whereNull('deleted_at')->first();
                }
            }
        }
        return null;
    }

    /** Bangladesh mobile format: 01[3-9]XXXXXXXX */
    private function bdPhoneRule(): array
    {
        return ['regex:/^01[3-9][0-9]{8}$/'];
    }

    /** NID allowed-lengths (config-driven) regex like ^(?:\d{10}|\d{13}|\d{17})$ */
    private function nidRegexFromConfig(): string
    {
        $lengths = array_values(array_filter((array) config('registration.allowed_nid_lengths', [10,13,17]), fn($v)=>is_numeric($v) && $v>0));
        $parts = array_map(fn($n)=>'\\d{'.((int)$n).'}', $lengths);
        $alt   = implode('|', $parts) ?: '\\d{10}';
        return '/^(?:'.$alt.')$/';
    }

    /** Central place to read max image KB (defaults to 1024) */
    private function maxImageKB(): int
    {
        return (int) config('registration.max_image_kb', 1024);
    }

    private function logActivity(string $action, int $userId, int $companyId, array $payload = []): void
    {
        DB::table('activity_logs')->insert([
            'company_id' => $companyId,
            'user_id'    => $userId,
            'action'     => $action,
            'table_name' => 'registration_master',
            'row_id'     => 0,
            'details'    => json_encode($payload),
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'time_local' => now(),
            'time_dhaka' => now(),
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /* ======== PUBLIC (non-Storage) IMAGE PATHS — single file per field per record ======== */

    /** e.g. assets/images/{countryNameLower}/{slug}/registration */
    private function publicBaseRelPath(object $companyRow): string
    {
        $country = DB::table('countries')->where('id', $companyRow->country_id)->first(['name','short_code']);
        $countryDir  = Str::of($country->name ?? 'Bangladesh')->lower()->value();
        $slug        = $companyRow->slug ?? 'company';
        return "assets/images/{$countryDir}/{$slug}/registration";
    }

    private function publicBaseAbsPath(object $companyRow): string
    {
        return public_path($this->publicBaseRelPath($companyRow));
    }

    private function ensureDir(string $absDir): void
    {
        if (!is_dir($absDir)) { @mkdir($absDir, 0775, true); }
    }

    private function makeFileName(object $companyRow, int $userId, string $field, string $ext): string
    {
        $country = DB::table('countries')->where('id', $companyRow->country_id)->first(['short_code']);
        $countryCode = strtoupper($country->short_code ?? 'BD');
        $slug        = $companyRow->slug ?? 'company';
        $ts          = now()->format('YmdHis');
        return "{$countryCode}_{$slug}_{$userId}_{$field}_{$ts}.{$ext}";
    }

    /**
     * Persist exactly one image for a field.
     * Safe to call after a successful DB commit.
     */
    private function persistSingleImage(
        object $companyRow,
        int $userId,
        string $field,
        ?\Illuminate\Http\UploadedFile $upload,
        ?string $tempUrl,
        ?string $existingRelPath = null
    ): ?string {
        if (!$upload && !$tempUrl) return $existingRelPath;

        $baseRel = $this->publicBaseRelPath($companyRow);
        $baseAbs = $this->publicBaseAbsPath($companyRow);
        $this->ensureDir($baseAbs);

        $ext = $upload?->getClientOriginalExtension();
        if (!$ext && $tempUrl) {
            $ext = pathinfo(parse_url($tempUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg';
        }
        $ext = strtolower($ext ?: 'jpg');

        $finalName = $this->makeFileName($companyRow, $userId, $field, $ext);
        $finalAbs  = rtrim($baseAbs, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$finalName;
        $finalRel  = rtrim($baseRel, '/').'/'.$finalName;

        // remove previous if any
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

    /* ======== TEMP UPLOAD HELPERS ======== */

    private function freshTempsFromThisPost(Request $request): array
    {
        $fields = ['photo','nid_front','nid_back','birth_certificate'];
        $out = [];
        $maxBytes = $this->maxImageKB() * 1024;

        foreach ($fields as $f) {
            if ($request->hasFile($f) && $request->file($f)->isValid()) {
                $file = $request->file($f);
                if (method_exists($file, 'getSize') && ($file->getSize() > $maxBytes)) {
                    continue;
                }
                $ext  = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $name = 'tmp_'.Str::random(10).'_'.time().'.'.$ext;
                $path = $file->storeAs('public/tmp/registration', $name);
                $out["temp_{$f}"] = Storage::url($path);
            }
        }
        return $out;
    }

    private function mergedTempUploads(Request $request): array
    {
        $sessionTemps = (array) session('_temp_uploads', []);
        $hiddenTemps = [];
        foreach (['photo','nid_front','nid_back','birth_certificate'] as $f) {
            $key = "temp_{$f}";
            $val = trim((string) $request->input($key, ''));
            if ($val !== '') $hiddenTemps[$key] = $val;
        }
        $freshTemps  = $this->freshTempsFromThisPost($request);
        $merged = array_filter($sessionTemps, fn($v)=>$v !== null && $v !== '');
        foreach ([$hiddenTemps, $freshTemps] as $bucket) {
            foreach ($bucket as $k=>$v) {
                if ($v !== null && $v !== '') $merged[$k] = $v;
            }
        }
        return $merged;
    }

    private function backWithInputAndTemp(Request $request, array $errors)
    {
        $temps = $this->mergedTempUploads($request);
        if (isset($errors['_e']) && !isset($errors['full_name'])) {
            $errors['full_name'] = is_array($errors['_e']) ? ($errors['_e'][0] ?? 'Validation error') : $errors['_e'];
        }
        return back()
            ->withErrors($errors)
            ->withInput()
            ->with('_temp_uploads', $temps);
    }

    private function normalizeYears($v): ?float
    {
        if ($v === null) return null;
        if (is_string($v)) {
            $v = str_replace(',', '.', trim($v));
        }
        if ($v === '' || !is_numeric($v)) return null;

        $s = (string)$v;
        if (!preg_match('/^(?:\d{1,3})(?:\.\d)?$/', $s)) {
            return null;
        }

        $f = (float)$v;
        if ($f < 0 || $f > 999.9) return null;

        return round($f, 1);
    }

    /* ======== ENTRY: KEY CHECK (AJAX) ======== */

    public function checkKey(Request $request, $company)
    {
        $uid = $this->currentUserId();
        if (!$uid) {
            return response()->json(['ok'=>false, 'message'=>'Login required'], 403);
        }

        $companyRow = $this->resolveCompany($company);
        if (!$companyRow) {
            return response()->json(['ok'=>false, 'message'=>'Company not found'], 404);
        }

        $exists = DB::table('registration_master')
            ->where('company_id', $companyRow->id)
            ->where('user_id', $uid)
            ->exists();

        if ($exists) {
            return response()->json([
                'ok'=>false,
                'message'=>'Not Allowed ! Registration in this company already Done or in processing.'
            ]);
        }

        $regKey = trim((string) $request->input('reg_key', ''));
        if ($regKey === '') {
            return response()->json(['ok'=>false, 'message'=>'Registration Key required']);
        }

        $keyRow = DB::table('company_reg_keys')
            ->where('Company_id', $companyRow->id)
            ->where('reg_key', $regKey)
            ->where('status', 1)
            ->first();

        if (!$keyRow) {
            return response()->json(['ok'=>false, 'message'=>'Invalid Registration Key. Try again.']);
        }

        $user = DB::table('users')->where('id',$uid)->first();
        session([
            'co_reg' => [
                'company_id'        => (int)$companyRow->id,
                'user_id'           => (int)$uid,
                'name'              => $user->name ?? ($user->full_name ?? 'User'),
                'email'             => $user->email ?? null,
                'reg_key'           => $regKey,
                'registration_type' => 'company_officer',
            ],
        ]);

        $slugOrId = $companyRow->slug ?? $companyRow->id;
        $redirect = route('registration.company_officer.create', ['company'=>$slugOrId]);

        return response()->json(['ok'=>true, 'redirect'=>$redirect]);
    }

    /* ======== CREATE ======== */

    public function create(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');

        $ctx = session('co_reg');
        abort_if(!$ctx || (int)$ctx['company_id'] !== (int)$companyRow->id || (int)$ctx['user_id'] !== $uid, 403, 'Invalid entry');

        $divisions = DB::table('divisions')->orderBy('short_code')->get(['id','name','short_code']);

        $degrees     = DB::table('degrees')->where('is_active',1)->orderBy('short_code')->get(['id','short_code','name']);
        $softwares   = DB::table('software_list')->where('status',1)->orderBy('software_name')->get(['id','software_name']);
        $skills      = DB::table('skills')->where('status',1)->orderBy('skill')->get(['id','skill']);
        $tasks       = DB::table('tasks_param')->where('status',1)->orderBy('Task_Param_Name')->get(['Task_Param_ID','Task_Param_Name']);
        $trainCats   = DB::table('training_category')
                        ->where('Company_id',$companyRow->id)->where('status',1)
                        ->orderBy('Training_Category_Name')
                        ->get(['Training_Category_Id','Training_Category_Name']);
        $professions = DB::table('professions')->orderBy('profession')->get(['id','profession']);

        return view('registration.company_officer.create', [
            'title'      => "{$companyRow->name} | Company Officer Registration",
            'company'    => $companyRow,
            'uid'        => $uid,
            'ctx'        => $ctx,
            'divisions'  => $divisions,
            'degrees'    => $degrees,
            'softwares'  => $softwares,
            'skills'     => $skills,
            'tasks'      => $tasks,
            'trainCats'  => $trainCats,
            'professions'=> $professions,
            'nidLengths' => (array) config('registration.allowed_nid_lengths', [10,13,17]),
            'tempUploads'=> session('_temp_uploads', []),
        ]);
    }

    /* ======== STORE ======== */

    public function store(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int)$companyRow->id;

        $ctx = session('co_reg');
        $hasCtx = $ctx && (int)$ctx['company_id'] === $companyId && (int)$ctx['user_id'] === $uid;
        $regKey = $hasCtx ? ($ctx['reg_key'] ?? null) : null;

        $allowedMimes = 'jpg,jpeg,png,webp,gif,jfif,bmp';
        $maxKB        = $this->maxImageKB();

        $rules = [
            'full_name'       => ['required','string','max:150'],
            'gender'          => ['required', Rule::in(['male','female','other'])],
            'date_of_birth'   => ['required','date','before:today'],

            'phone'           => array_merge(
                ['required','string','max:30'],
                $this->bdPhoneRule(),
                [
                    Rule::unique('registration_master','phone')
                        ->where(fn($q) => $q->where('company_id',$companyId)),
                ]
            ),
            'email'           => [
                'required','email','max:150',
                Rule::unique('registration_master','email')
                    ->where(fn($q) => $q->where('company_id',$companyId)),
            ],

            'employee_id'     => [
                'required','string','max:60',
                Rule::unique('registration_master','Employee_ID')
                    ->where(fn($q) => $q->where('company_id',$companyId)),
            ],

            'division_id'     => ['required','integer','exists:divisions,id'],
            'district_id'     => ['required','integer','exists:districts,id'],
            'upazila_id'      => ['required','integer','exists:upazilas,id'],
            'thana_id'        => ['required','integer','exists:thanas,id'],

            'person_type'     => ['required', Rule::in(['J','B','H','S','P','O'])],
            'profession'      => ['required','integer','exists:professions,id'],
            'present_address' => ['required','string','max:255'],

            'photo'           => ['nullable','file','mimes:'.$allowedMimes,'max:'.$maxKB],

            'nid_number'      => ['nullable','string','regex:'.$this->nidRegexFromConfig()],
            'nid_front'       => ['nullable','file','mimes:'.$allowedMimes,'max:'.$maxKB],
            'nid_back'        => ['nullable','file','mimes:'.$allowedMimes,'max:'.$maxKB],
            'birth_certificate'=> ['nullable','file','mimes:'.$allowedMimes,'max:'.$maxKB],

            'skill_ids'          => ['required','array','min:1'],
            'skill_ids.*'        => ['integer','exists:skills,id'],
            'task_ids'           => ['required','array','min:1'],
            'task_ids.*'         => ['integer','exists:tasks_param,Task_Param_ID'],

            'software_ids'       => ['nullable','array'],
            'software_ids.*'     => ['integer','exists:software_list,id'],
            'software_years'     => ['nullable','array'],

            'temp_photo'            => ['nullable','string'],
            'temp_nid_front'        => ['nullable','string'],
            'temp_nid_back'         => ['nullable','string'],
            'temp_birth_certificate'=> ['nullable','string'],
        ];

        if (trim((string)$request->input('nid_number','')) !== '') {
            $rules['nid_number'][] =
                Rule::unique('registration_master','NID')
                    ->where(fn($q) => $q->where('company_id',$companyId));
        }

        $messages = [
            'phone.unique'     => 'This mobile number is already registered for this company.',
            'email.unique'     => 'This email is already registered for this company.',
            'phone.regex'      => 'Enter a valid Bangladesh mobile number (01XXXXXXXXX).',
            'date_of_birth.required' => 'Date of Birth is required.',
            'date_of_birth.before'   => 'Date of Birth cannot be in the future.',
            'photo.max'        => "Photo must be at most {$this->maxImageKB()}KB.",
            'nid_front.max'    => "NID front must be at most {$this->maxImageKB()}KB.",
            'nid_back.max'     => "NID back must be at most {$this->maxImageKB()}KB.",
            'birth_certificate.max' => "Birth certificate must be at most {$this->maxImageKB()}KB.",
            'nid_number.regex' => 'NID length incorrect.',
            'nid_number.unique'=> 'Same NID already used in this Company.',
            'employee_id.unique' => 'Employee ID already used in this Company.',
            'skill_ids.required' => 'Select at least one skill.',
            'task_ids.required'  => 'Select at least one preferred area.',
            'profession.required'=> 'Please select your profession.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->backWithInputAndTemp($request, $validator->errors()->toArray());
        }
        $data = $validator->validated();

        // Sanitize validated skills and tasks
        $skillIds = collect($data['skill_ids'] ?? [])
            ->map(fn($v)=>(int)$v)->filter(fn($v)=>$v>0)->unique()->values();
        $taskIds = collect($data['task_ids'] ?? [])
            ->map(fn($v)=>(int)$v)->filter(fn($v)=>$v>0)->unique()->values();

        if ($skillIds->isEmpty()) {
            return $this->backWithInputAndTemp($request, ['skill_ids' => 'Select at least one valid skill.']);
        }

        // Cross-field requirements for docs
        $hasPhoto = $request->hasFile('photo') || filled($data['temp_photo'] ?? null);
        if (!$hasPhoto) {
            return $this->backWithInputAndTemp($request, ['photo' => 'Photo image is required.']);
        }

        $hasNid = filled($data['nid_number'] ?? null);
        if ($hasNid) {
            $hasNidFront = $request->hasFile('nid_front') || filled($data['temp_nid_front'] ?? null);
            $hasNidBack  = $request->hasFile('nid_back')  || filled($data['temp_nid_back'] ?? null);
            if (!$hasNidFront || !$hasNidBack) {
                return $this->backWithInputAndTemp($request, ['nid_front' => 'Both NID front and back images are required when NID number is provided.']);
            }
        } else {
            $hasBirth = $request->hasFile('birth_certificate') || filled($data['temp_birth_certificate'] ?? null);
            if (!$hasBirth) {
                return $this->backWithInputAndTemp($request, ['birth_certificate' => 'Birth certificate image is required if NID is not provided.']);
            }
        }

        // Education rows
        $edu = collect($request->input('edu', []))
            ->filter(fn($r) =>
                !empty($r['degree_id']) ||
                !empty($r['institution']) ||
                !empty($r['passing_year']) ||
                !empty($r['result_type']) ||
                !empty($r['score']) ||
                !empty($r['out_of'])
            )->values();

        if ($edu->isEmpty()) {
            return $this->backWithInputAndTemp($request, ['edu.0.degree_id' => 'At least one education record is required.']);
        }
        $dupEdu = [];
        foreach ($edu as $i => $r) {
            if (
                empty($r['degree_id']) ||
                empty($r['institution']) ||
                empty($r['passing_year']) ||
                empty($r['result_type']) ||
                empty($r['score']) ||
                (!isset($r['out_of']))
            ) {
                return $this->backWithInputAndTemp($request, ["edu.$i.degree_id" => 'All fields are mandatory in each education row.']);
            }
            if ((int)$r['passing_year'] > (int)date('Y')) {
                return $this->backWithInputAndTemp($request, ["edu.$i.passing_year" => 'Passing year cannot be in the future.']);
            }
            $key = implode('|', [
                (int)$r['degree_id'],
                Str::lower(trim($r['institution'])),
                (int)$r['passing_year'],
                $r['result_type'],
                trim($r['score']),
                (int)$r['out_of'],
            ]);
            if (isset($dupEdu[$key])) {
                return $this->backWithInputAndTemp($request, ["edu.$i.institution" => 'Duplicate education record detected.']);
            }
            $dupEdu[$key] = true;
        }

        // Job rows
        $job = collect($request->input('job', []))
            ->filter(fn($r) =>
                !empty($r['employer']) || !empty($r['job_title']) || !empty($r['join_date']) ||
                (!empty($r['is_present'])) || !empty($r['end_date'])
            )->values();

        $dupJob = [];
        foreach ($job as $i => $rowData) {
            $r = array_map(fn($v)=> is_string($v)?trim($v):$v, (array)$rowData);

            if (empty($r['employer']) || empty($r['job_title']) || empty($r['join_date']) || empty($r['is_present'])) {
                return $this->backWithInputAndTemp($request, ["job.$i.employer" => 'Employer, Job title, Joining date and Present-job flag are required in each job row.']);
            }

            try { $join = Carbon::parse($r['join_date'])->startOfDay(); }
            catch (\Throwable $e) { return $this->backWithInputAndTemp($request, ["job.$i.join_date" => 'Joining date is invalid.']); }

            if (($r['is_present'] ?? 'N') === 'Y') {
                $r['end_date'] = null;
            } else {
                if (empty($r['end_date'])) {
                    return $this->backWithInputAndTemp($request, ["job.$i.end_date" => 'End date is required when not a present job.']);
                }
                try { $end = Carbon::parse($r['end_date'])->startOfDay(); }
                catch (\Throwable $e) { return $this->backWithInputAndTemp($request, ["job.$i.end_date" => 'End date is invalid.']); }
                if ($end->lte($join)) {
                    return $this->backWithInputAndTemp($request, ["job.$i.end_date" => 'End date must be after the joining date.']);
                }
            }

            $key = implode('|', [Str::lower(trim($r['employer'])), Str::lower(trim($r['job_title'])), $join->toDateString()]);
            if (isset($dupJob[$key])) {
                return $this->backWithInputAndTemp($request, ["job.$i.employer" => 'Duplicate job experience detected.']);
            }
            $dupJob[$key] = true;

            $job[$i] = $r;
        }

        // Training rows — remarks removed
        $train = collect($request->input('train', []))
            ->filter(fn($r) => !empty($r['category_id']) || !empty($r['training_id']))
            ->values();

        $dupTrn = [];
        foreach ($train as $i => $r) {
            if (empty($r['category_id']) || empty($r['training_id'])) {
                return $this->backWithInputAndTemp($request, ["train.$i.category_id" => 'Category and Training are mandatory in each training row.']);
            }
            $key = ((int)$r['category_id']).'|'.((int)$r['training_id']);
            if (isset($dupTrn[$key])) {
                return $this->backWithInputAndTemp($request, ["train.$i.training_id" => 'Duplicate training row detected.']);
            }
            $dupTrn[$key] = true;
        }

        /* ---------------- Software sanitize ---------------- */
        $softwareIds = collect((array) $request->input('software_ids', []))
            ->map(fn($v) => is_numeric($v) ? (int)$v : null)
            ->filter(fn($v) => $v !== null && $v > 0)
            ->unique()
            ->values();

        if ($softwareIds->isNotEmpty()) {
            $existing = DB::table('software_list')
                ->whereIn('id', $softwareIds)
                ->pluck('id')
                ->map(fn($v) => (int)$v)
                ->values();

            $softwareIds = $softwareIds->intersect($existing)->values();
        }

        $yearsPost = (array) $request->input('software_years', []);
        $yearsMap  = [];
        $assoc     = collect(array_keys($yearsPost))->contains(fn($k)=>!is_numeric($k));

        if ($assoc) {
            foreach ($softwareIds as $sid) {
                $yearsMap[$sid] = $yearsPost[$sid] ?? null;
            }
        } else {
            $vals = array_values($yearsPost);
            foreach ($softwareIds as $i => $sid) {
                $yearsMap[$sid] = $vals[$i] ?? null;
            }
        }

        $softErrors = [];
        foreach ($softwareIds as $sid) {
            $norm = $this->normalizeYears($yearsMap[$sid] ?? null);
            if ($norm === null) {
                $softErrors["software_years.$sid"] =
                    'Enter years with at most 1 decimal (e.g., 2 or 2.3), between 0 and 999.9.';
            } else {
                $yearsMap[$sid] = $norm;
            }
        }
        if (!empty($softErrors)) {
            return $this->backWithInputAndTemp($request, $softErrors);
        }
        foreach ($softwareIds as $sid) {
            if (!array_key_exists($sid, $yearsMap) || $yearsMap[$sid] === null) {
                $yearsMap[$sid] = '0.0';
            }
        }

        // Defer file moves until after commit
        $plan = [
            'photo' => [$request->file('photo'), $data['temp_photo'] ?? null],
            'nid_front' => [$request->file('nid_front'), $data['temp_nid_front'] ?? null],
            'nid_back'  => [$request->file('nid_back'),  $data['temp_nid_back'] ?? null],
            'birth_certificate' => [$request->file('birth_certificate'), $data['temp_birth_certificate'] ?? null],
        ];

        try {
            $regId = null;

            DB::transaction(function () use ($uid, $companyRow, $companyId, $data, $edu, $job, $train, $regKey, $softwareIds, $yearsMap, $skillIds, $taskIds, &$regId) {
                // 1) Master without file paths
                $regId = DB::table('registration_master')->insertGetId([
                    'company_id'        => $companyId,
                    'user_id'           => $uid,
                    'registration_type' => 'company_officer',
                    'Reg_Key'           => $regKey,
                    'full_name'         => $data['full_name'],
                    'gender'            => $data['gender'],
                    'date_of_birth'     => $data['date_of_birth'] ?? null,
                    'phone'             => $data['phone'],
                    'email'             => $data['email'],
                    'Employee_ID'       => $data['employee_id'],
                    'division_id'       => (int)$data['division_id'],
                    'district_id'       => (int)$data['district_id'],
                    'upazila_id'        => (int)$data['upazila_id'],
                    'thana_id'          => (int)$data['thana_id'],
                    'person_type'       => $data['person_type'],
                    'profession'        => (int)$data['profession'],
                    'present_address'   => $data['present_address'],
                    'approval_status'   => 'pending',
                    'status'            => 0,
                    'created_by'        => $uid,
                    'updated_by'        => $uid,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                // 2) Details
                foreach ($edu as $r) {
                    DB::table('education_background')->insert([
                        'Company_id'      => $companyId,
                        'registration_id' => $regId,
                        'degree_id'       => (int)$r['degree_id'],
                        'Institution'     => $r['institution'],
                        'Passing_Year'    => (int)$r['passing_year'],
                        'Result_Type'     => $r['result_type'],
                        'obtained_grade_or_score' => $r['score'],
                        'Out_of'          => (int)$r['out_of'],
                        'status'          => 1,
                        'created_by'      => $uid,
                        'updated_by'      => $uid,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }

                foreach ($job as $r) {
                    DB::table('job_experiences')->insert([
                        'Company_id'      => $companyId,
                        'registration_id' => $regId,
                        'Employer'        => $r['employer'],
                        'Job_title'       => $r['job_title'],
                        'Joining_date'    => $r['join_date'],
                        'End_date'        => ($r['is_present'] ?? 'N') === 'Y' ? null : ($r['end_date'] ?? null),
                        'is_present_job'  => ($r['is_present'] ?? 'N') === 'Y' ? 'Y' : 'N',
                        'status'          => 1,
                        'created_by'      => $uid,
                        'updated_by'      => $uid,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }

                foreach ($softwareIds as $sid) {
                    DB::table('expertise_on_softwares')->insert([
                        'Company_id'          => $companyId,
                        'registration_id'     => $regId,
                        'expert_on_software'  => $sid,
                        'experience_in_years' => $yearsMap[$sid] ?? '0.0',
                        'status'              => 1,
                        'created_by'          => $uid,
                        'updated_by'          => $uid,
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                }

                foreach ($skillIds as $skid) {
                    DB::table('person_skills')->insert([
                        'Company_id'      => $companyId,
                        'registration_id' => $regId,
                        'skill'           => $skid,
                        'status'          => 1,
                        'created_by'      => $uid,
                        'updated_by'      => $uid,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }

                foreach ($taskIds as $tid) {
                    DB::table('preffered_area_of_job')->insert([
                        'Company_id'      => $companyId,
                        'registration_id' => $regId,
                        'Task_Param_ID'   => $tid,
                        'status'          => 1,
                        'created_by'      => $uid,
                        'updated_by'      => $uid,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }

                foreach ($train as $r) {
                    DB::table('training_required')->insert([
                        'Company_id'          => $companyId,
                        'registration_id'     => $regId,
                        'Training_Category_Id'=> (int)$r['category_id'],
                        'Training_ID'         => (int)$r['training_id'],
                        'status'              => 1,
                        'created_by'          => $uid,
                        'updated_by'          => $uid,
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                }

                if ($regKey) {
                    DB::table('company_reg_keys')
                        ->where('Company_id',$companyId)
                        ->where('reg_key',$regKey)
                        ->update([
                            'status'     => 0,
                            'updated_by' => $uid,
                            'updated_at' => now(),
                        ]);
                }

                $this->logActivity('create', $uid, $companyId, ['type'=>'company_officer','reg_id'=>$regId]);
            });

            // After successful commit: move files and update paths
            DB::afterCommit(function () use ($uid, $companyRow, $regId, $data, $plan) {
                if (!$regId) return;

                $photoRel = $this->persistSingleImage($companyRow, $uid, 'photo', $plan['photo'][0], $plan['photo'][1], null);
                $nidFRel  = $this->persistSingleImage($companyRow, $uid, 'nid_front', $plan['nid_front'][0], $plan['nid_front'][1], null);
                $nidBRel  = $this->persistSingleImage($companyRow, $uid, 'nid_back',  $plan['nid_back'][0],  $plan['nid_back'][1],  null);
                $bcRel    = $this->persistSingleImage($companyRow, $uid, 'birth_certificate', $plan['birth_certificate'][0], $plan['birth_certificate'][1], null);

                DB::table('registration_master')->where('id',$regId)->update([
                    'Photo'                   => $photoRel,
                    'NID'                     => $data['nid_number'] ?? null,
                    'NID_Photo_Front_Page'    => $nidFRel,
                    'NID_Photo_Back_Page'     => $nidBRel,
                    'Birth_Certificate_Photo' => $bcRel,
                    'updated_at'              => now(),
                ]);
            });

        } catch (QueryException $e) {
            try {
                \Log::error('CompanyOfficer.store QueryException', [
                    'sql'       => method_exists($e, 'getSql') ? $e->getSql() : null,
                    'bindings'  => method_exists($e, 'getBindings') ? $e->getBindings() : null,
                    'msg'       => $e->getMessage(),
                    'errorInfo' => $e->errorInfo ?? null,
                    'code'      => $e->errorInfo[1] ?? null,
                ]);
            } catch (\Throwable $ignore) {}

            $msg  = $e->getMessage();
            $code = $e->errorInfo[1] ?? null;

            if (str_contains($msg, 'uq_reg_software') || ($code === 1062 && str_contains($msg, 'expertise_on_softwares'))) {
                return $this->backWithInputAndTemp($request, ['software_ids' => 'Duplicate software entry detected for this registration.']);
            }
            if (str_contains($msg, 'experience_in_years') && ($code === 1265 || $code === 1366 || str_contains($msg,'Data truncated'))) {
                return $this->backWithInputAndTemp($request, ['software_years.*' => 'Years must be a number like 2 or 2.3 (max one decimal).']);
            }

            // Precise FK mapping
            if ($code === 1452 || str_contains($msg, 'foreign key constraint')) {
                if (str_contains($msg, 'person_skills') || str_contains($msg, 'skills') || str_contains($msg, 'fk_ps_skill')) {
                    return $this->backWithInputAndTemp($request, ['skill_ids' => 'Failed to save skills. Please reselect valid skills.']);
                }
                if (str_contains($msg, 'preffered_area_of_job') || str_contains($msg, 'tasks_param') || str_contains($msg, 'Task_Param_ID')) {
                    return $this->backWithInputAndTemp($request, ['task_ids' => 'Failed to save preferred areas. Please reselect valid items.']);
                }
                if (str_contains($msg, 'expertise_on_softwares') || str_contains($msg, 'software_list') || str_contains($msg, 'expert_on_software')) {
                    return $this->backWithInputAndTemp($request, ['software_ids' => 'Failed to save software experience. Please reselect software.']);
                }
                return $this->backWithInputAndTemp($request, ['_e' => 'A reference failed while saving details. Check your selections.']);
            }

            if (str_contains($msg, 'uq_reg_company_phone') || str_contains($msg, 'phone')) {
                return $this->backWithInputAndTemp($request, ['phone' => 'This mobile number is already registered for this company.']);
            }
            if (str_contains($msg, 'uq_reg_company_email') || str_contains($msg, 'email')) {
                return $this->backWithInputAndTemp($request, ['email' => 'This email is already registered for this company.']);
            }
            if (str_contains($msg, 'Passing_Year')) {
                return $this->backWithInputAndTemp($request, ['edu.0.passing_year' => 'Passing year cannot be in the future.']);
            }

            return $this->backWithInputAndTemp($request, ['_e' => 'Unable to save. Please correct highlighted fields and try again.']);
        }

        session()->forget('co_reg');
        session()->forget('_temp_uploads');

        $slugOrId = $companyRow->slug ?? $companyRow->id;
        return redirect()->route('backend.company.dashboard.index', ['company' => $slugOrId])
            ->with('status','Registration submitted for approval.');
    }

    /* ======== EDIT ======== */

public function edit(Request $request, $company)
{
    $uid = $this->currentUserId();
    abort_if(!$uid, 403, 'Login required');

    $companyRow = $this->resolveCompany($company);
    abort_if(!$companyRow, 404, 'Company not found');
    $companyId = (int)$companyRow->id;

    $row = DB::table('registration_master')
        ->where('company_id',$companyId)
        ->where('user_id',$uid)
        ->where('registration_type','company_officer')
        ->orderByDesc('id')
        ->first();
    abort_if(!$row, 404, 'Registration not found');

    // Normalize column names expected by the blade
    $row->employee_id        = $row->Employee_ID ?? null;
    $row->profession         = $row->Profession ?? null;
    $row->photo              = $row->Photo ?? null;
    $row->nid_number         = $row->NID ?? null;
    $row->nid_front          = $row->NID_Photo_Front_Page ?? null;
    $row->nid_back           = $row->NID_Photo_Back_Page ?? null;
    $row->birth_certificate  = $row->Birth_Certificate_Photo ?? null;

    $division = $row->division_id
        ? DB::table('divisions')->where('id',$row->division_id)->first(['short_code','name','id'])
        : null;
    $district = $row->district_id
        ? DB::table('districts')->where('id',$row->district_id)->first(['short_code','name','id'])
        : null;
    $upazila  = $row->upazila_id
        ? DB::table('upazilas')->where('id',$row->upazila_id)->first(['short_code','name','id'])
        : null;
    $thana    = $row->thana_id
        ? DB::table('thanas')->where('id',$row->thana_id)->first(['short_code','name','id'])
        : null;

    $degrees     = DB::table('degrees')->where('is_active',1)->orderBy('short_code')->get(['id','short_code','name']);
    $softwares   = DB::table('software_list')->where('status',1)->orderBy('software_name')->get(['id','software_name']);
    $skills      = DB::table('skills')->where('status',1)->orderBy('skill')->get(['id','skill']);
    $tasks       = DB::table('tasks_param')->where('status',1)->orderBy('Task_Param_Name')->get(['Task_Param_ID','Task_Param_Name']);
    $trainCats   = DB::table('training_category')
                    ->where('Company_id',$companyId)->where('status',1)
                    ->orderBy('Training_Category_Name')
                    ->get(['Training_Category_Id','Training_Category_Name']);
    $professions = DB::table('professions')->orderBy('profession')->get(['id','profession']);

    $selSoft   = DB::table('expertise_on_softwares')
                    ->where('Company_id',$companyId)->where('registration_id',$row->id)
                    ->pluck('expert_on_software')->all();
    $softExp   = DB::table('expertise_on_softwares')
                    ->where('Company_id',$companyId)->where('registration_id',$row->id)
                    ->pluck('experience_in_years','expert_on_software')->toArray();
    $selSkills = DB::table('person_skills')
                    ->where('Company_id',$companyId)->where('registration_id',$row->id)
                    ->pluck('skill')->all();
    $selTasks  = DB::table('preffered_area_of_job')
                    ->where('Company_id',$companyId)->where('registration_id',$row->id)
                    ->pluck('Task_Param_ID')->all();

    $eduRows = DB::table('education_background')
        ->where('Company_id',$companyId)->where('registration_id',$row->id)
        ->orderBy('id')->get();

    $jobRows = DB::table('job_experiences')
        ->where('Company_id',$companyId)->where('registration_id',$row->id)
        ->orderBy('id')->get();

    $trainRows = DB::table('training_required')
        ->where('Company_id',$companyId)->where('registration_id',$row->id)
        ->orderBy('Training_Category_Id')->get();

    return view('registration.company_officer.edit', [
        'title'       => "{$companyRow->name} | Edit Officer Registration",
        'company'     => $companyRow,
        'row'         => $row,
        'division'    => $division,
        'district'    => $district,
        'upazila'     => $upazila,
        'thana'       => $thana,
        'degrees'     => $degrees,
        'softwares'   => $softwares,
        'skills'      => $skills,
        'tasks'       => $tasks,
        'trainCats'   => $trainCats,
        'professions' => $professions,
        'selSoft'     => $selSoft,
        'softExp'     => $softExp,
        'selSkills'   => $selSkills,
        'selTasks'    => $selTasks,
        'eduRows'     => $eduRows,
        'jobRows'     => $jobRows,
        'trainRows'   => $trainRows,
        'nidLengths'  => (array) config('registration.allowed_nid_lengths', [10,13,17]),
        'tempUploads' => session('_temp_uploads', []),
    ]);
}

public function update(Request $request, $company)
{
    $uid = $this->currentUserId();
    abort_if(!$uid, 403, 'Login required');

    $companyRow = $this->resolveCompany($company);
    abort_if(!$companyRow, 404, 'Company not found');
    $companyId = (int)$companyRow->id;

    $row = DB::table('registration_master')
        ->where('company_id',$companyId)->where('user_id',$uid)
        ->where('registration_type','company_officer')
        ->orderByDesc('id')->first();
    abort_if(!$row, 404, 'Registration not found');

    $allowedMimes = 'jpg,jpeg,png,webp,gif,jfif,bmp';
    $maxKB        = $this->maxImageKB();

    $rules = [
        'full_name'       => ['required','string','max:150'],
        'gender'          => ['required', Rule::in(['male','female','other'])],
        'date_of_birth'   => ['required','date','before:today'],

        'phone'           => array_merge(
            ['required','string','max:30'],
            $this->bdPhoneRule(),
            [
                Rule::unique('registration_master','phone')
                    ->where(fn($q) => $q->where('company_id',$companyId))
                    ->ignore($row->id, 'id'),
            ]
        ),
        'email'           => [
            'required','email','max:150',
            Rule::unique('registration_master','email')
                ->where(fn($q) => $q->where('company_id',$companyId))
                ->ignore($row->id, 'id'),
        ],
        'employee_id'     => [
            'required','string','max:60',
            Rule::unique('registration_master','Employee_ID')
                ->where(fn($q) => $q->where('company_id',$companyId))
                ->ignore($row->id, 'id'),
        ],

        // Division/District/Upazila locked in UI. Thana editable only.
        'thana_id'        => ['required','integer','exists:thanas,id'],
        'person_type'     => ['required', Rule::in(['J','B','H','S','P','O'])],
        'profession'      => ['required','integer','exists:professions,id'],
        'present_address' => ['required','string','max:255'],

        'photo'           => ['nullable','file','mimes:'.$allowedMimes,'max:'.$maxKB],

        'nid_number'      => ['nullable','string','regex:'.$this->nidRegexFromConfig()],
        'nid_front'       => ['nullable','file','mimes:'.$allowedMimes,'max:'.$maxKB],
        'nid_back'        => ['nullable','file','mimes:'.$allowedMimes,'max:'.$maxKB],
        'birth_certificate'=> ['nullable','file','mimes:'.$allowedMimes,'max:'.$maxKB],

        'edu.*.id'           => ['nullable','integer'],
        'edu.*.degree_id'    => ['nullable','integer','exists:degrees,id'],
        'edu.*.institution'  => ['nullable','string','max:180'],
        'edu.*.passing_year' => ['nullable','digits:4'],
        'edu.*.result_type'  => ['nullable', Rule::in(['GPA','CGPA','Division','Class','Percentage'])],
        'edu.*.score'        => ['nullable','string','max:20'],
        'edu.*.out_of'       => ['nullable','integer','min:0'],

        'job.*.id'           => ['nullable','integer'],
        'job.*.employer'     => ['nullable','string','max:180'],
        'job.*.job_title'    => ['nullable','string','max:150'],
        'job.*.join_date'    => ['nullable','date','before_or_equal:today'],
        'job.*.is_present'   => ['nullable', Rule::in(['Y','N'])],
        'job.*.end_date'     => ['nullable','date'],

        'software_ids'       => ['array'],
        'software_ids.*'     => ['integer','exists:software_list,id'],
        'software_years'     => ['array'],

        'skill_ids'          => ['required','array','min:1'],
        'skill_ids.*'        => ['integer','exists:skills,id'],

        'task_ids'           => ['required','array','min:1'],
        'task_ids.*'         => ['integer','exists:tasks_param,Task_Param_ID'],

        'train.*.id'          => ['nullable','integer'],
        'train.*.category_id' => ['nullable','integer'],
        'train.*.training_id' => ['nullable','integer'],

        'temp_photo'            => ['nullable','string'],
        'temp_nid_front'        => ['nullable','string'],
        'temp_nid_back'         => ['nullable','string'],
        'temp_birth_certificate'=> ['nullable','string'],
    ];

    if (trim((string)$request->input('nid_number','')) !== '') {
        $rules['nid_number'][] =
            Rule::unique('registration_master','NID')
                ->where(fn($q) => $q->where('company_id',$companyId))
                ->ignore($row->id, 'id');
    }

    $messages = [
        'phone.unique'       => 'This mobile number is already registered for this company.',
        'email.unique'       => 'This email is already registered for this company.',
        'phone.regex'        => 'Enter a valid Bangladesh mobile number (01XXXXXXXXX).',
        'nid_number.regex'   => 'NID length incorrect.',
        'nid_number.unique'  => 'Same NID already used in this Company.',
        'employee_id.unique' => 'Employee ID already used in this Company.',
        'skill_ids.required' => 'Select at least one skill.',
        'task_ids.required'  => 'Select at least one preferred area.',
        'profession.required'=> 'Please select your profession.',
    ];

    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
        return $this->backWithInputAndTemp($request, $validator->errors()->toArray());
    }
    $data = $validator->validated();

    $skillIds = collect($data['skill_ids'] ?? [])
        ->map(fn($v)=>(int)$v)->filter(fn($v)=>$v>0)->unique()->values();
    $taskIds = collect($data['task_ids'] ?? [])
        ->map(fn($v)=>(int)$v)->filter(fn($v)=>$v>0)->unique()->values();
    if ($skillIds->isEmpty()) {
        return $this->backWithInputAndTemp($request, ['skill_ids' => 'Select at least one valid skill.']);
    }

    $hasNid = !empty($data['nid_number']) || $row->NID;
    if ($hasNid) {
        $hasFront = $row->NID_Photo_Front_Page || $request->hasFile('nid_front') || filled($data['temp_nid_front'] ?? null);
        $hasBack  = $row->NID_Photo_Back_Page  || $request->hasFile('nid_back')  || filled($data['temp_nid_back'] ?? null);
        if (!$hasFront || !$hasBack) {
            return $this->backWithInputAndTemp($request, ['nid_front' => 'Both NID front and back images are required when NID number is provided.']);
        }
    } else {
        $hasBirth = $row->Birth_Certificate_Photo || $request->hasFile('birth_certificate') || filled($data['temp_birth_certificate'] ?? null);
        if (!$hasBirth) {
            return $this->backWithInputAndTemp($request, ['birth_certificate' => 'Birth certificate image is required if NID is not provided.']);
        }
    }

    $edu = collect($request->input('edu', []))
        ->filter(fn($r) =>
            !empty($r['degree_id']) ||
            !empty($r['institution']) ||
            !empty($r['passing_year']) ||
            !empty($r['result_type']) ||
            !empty($r['score']) ||
            !empty($r['out_of'])
        )->values();

    if ($edu->isEmpty()) {
        return $this->backWithInputAndTemp($request, ['edu.0.degree_id' => 'At least one education record is required.']);
    }
    $dupEdu = [];
    foreach ($edu as $i => $r) {
        if (
            empty($r['degree_id']) ||
            empty($r['institution']) ||
            empty($r['passing_year']) ||
            empty($r['result_type']) ||
            empty($r['score']) ||
            (!isset($r['out_of']))
        ) {
            return $this->backWithInputAndTemp($request, ["edu.$i.degree_id" => 'All fields are mandatory in each education row.']);
        }
        if ((int)$r['passing_year'] > (int)date('Y')) {
            return $this->backWithInputAndTemp($request, ["edu.$i.passing_year" => 'Passing year cannot be in the future.']);
        }
        $key = implode('|', [
            (int)$r['degree_id'],
            Str::lower(trim($r['institution'])),
            (int)$r['passing_year'],
            $r['result_type'],
            trim($r['score']),
            (int)$r['out_of'],
        ]);
        if (isset($dupEdu[$key])) {
            return $this->backWithInputAndTemp($request, ["edu.$i.institution" => 'Duplicate education record detected.']);
        }
        $dupEdu[$key] = true;
    }

    $job = collect($request->input('job', []))
        ->filter(fn($r) =>
            !empty($r['employer']) || !empty($r['job_title']) || !empty($r['join_date']) ||
            (!empty($r['is_present'])) || !empty($r['end_date'])
        )->values();

    $dupJob = [];
    foreach ($job as $i => $rowData) {
        $r = array_map(fn($v)=> is_string($v)?trim($v):$v, (array)$rowData);

        if (empty($r['employer']) || empty($r['job_title']) || empty($r['join_date']) || empty($r['is_present'])) {
            return $this->backWithInputAndTemp($request, ["job.$i.employer" => 'Employer, Job title, Joining date and Present-job flag are required in each job row.']);
        }

        try { $join = Carbon::parse($r['join_date'])->startOfDay(); }
        catch (\Throwable $e) { return $this->backWithInputAndTemp($request, ["job.$i.join_date" => 'Joining date is invalid.']); }

        if (($r['is_present'] ?? 'N') === 'Y') {
            $r['end_date'] = null;
        } else {
            if (empty($r['end_date'])) {
                return $this->backWithInputAndTemp($request, ["job.$i.end_date" => 'End date is required when not a present job.']);
            }
            try { $end = Carbon::parse($r['end_date'])->startOfDay(); }
            catch (\Throwable $e) { return $this->backWithInputAndTemp($request, ["job.$i.end_date" => 'End date is invalid.']); }
            if ($end->lte($join)) {
                return $this->backWithInputAndTemp($request, ["job.$i.end_date" => 'End date must be after the joining date.']);
            }
        }

        $key = implode('|', [Str::lower(trim($r['employer'])), Str::lower(trim($r['job_title'])), $join->toDateString()]);
        if (isset($dupJob[$key])) {
            return $this->backWithInputAndTemp($request, ["job.$i.employer" => 'Duplicate job experience detected.']);
        }
        $dupJob[$key] = true;

        $job[$i] = $r;
    }

    $train = collect($request->input('train', []))
        ->filter(fn($r) => !empty($r['category_id']) || !empty($r['training_id']))
        ->values();

    $dupTrn = [];
    foreach ($train as $i => $r) {
        if (empty($r['category_id']) || empty($r['training_id'])) {
            return $this->backWithInputAndTemp($request, ["train.$i.category_id" => 'Category and Training are mandatory in each training row.']);
        }
        $key = ((int)$r['category_id']).'|'.((int)$r['training_id']);
        if (isset($dupTrn[$key])) {
            return $this->backWithInputAndTemp($request, ["train.$i.training_id" => 'Duplicate training row detected.']);
        }
        $dupTrn[$key] = true;
    }

    $softwareIds = collect((array) $request->input('software_ids', []))
        ->map(fn($v) => is_numeric($v) ? (int)$v : null)
        ->filter(fn($v) => $v !== null && $v > 0)
        ->unique()
        ->values();

    if ($softwareIds->isNotEmpty()) {
        $existing = DB::table('software_list')
            ->whereIn('id', $softwareIds)
            ->pluck('id')
            ->map(fn($v) => (int)$v)
            ->values();

        $softwareIds = $softwareIds->intersect($existing)->values();
    }

    $yearsPost = (array) $request->input('software_years', []);
    $yearsMap  = [];
    $assoc     = collect(array_keys($yearsPost))->contains(fn($k)=>!is_numeric($k));

    if ($assoc) {
        foreach ($softwareIds as $sid) {
            $yearsMap[$sid] = $yearsPost[$sid] ?? null;
        }
    } else {
        $vals = array_values($yearsPost);
        foreach ($softwareIds as $i => $sid) {
            $yearsMap[$sid] = $vals[$i] ?? null;
        }
    }

    $softErrors = [];
    foreach ($softwareIds as $sid) {
        $norm = $this->normalizeYears($yearsMap[$sid] ?? null);
        if ($norm === null) {
            $softErrors["software_years.$sid"] =
                'Enter years with at most 1 decimal (e.g., 2 or 2.3), between 0 and 999.9.';
        } else {
            $yearsMap[$sid] = $norm;
        }
    }
    if (!empty($softErrors)) {
        return $this->backWithInputAndTemp($request, $softErrors);
    }
    foreach ($softwareIds as $sid) {
        if (!array_key_exists($sid, $yearsMap) || $yearsMap[$sid] === null) {
            $yearsMap[$sid] = '0.0';
        }
    }

    $plan = [
        'photo' => [$request->file('photo'), $data['temp_photo'] ?? null, $row->Photo],
        'nid_front' => [$request->file('nid_front'), $data['temp_nid_front'] ?? null, $row->NID_Photo_Front_Page],
        'nid_back'  => [$request->file('nid_back'),  $data['temp_nid_back'] ?? null,  $row->NID_Photo_Back_Page],
        'birth_certificate' => [$request->file('birth_certificate'), $data['temp_birth_certificate'] ?? null, $row->Birth_Certificate_Photo],
    ];

    try {
        DB::transaction(function () use ($uid, $companyId, $row, $data, $edu, $job, $train, $softwareIds, $yearsMap, $skillIds, $taskIds) {

            DB::table('registration_master')->where('id',$row->id)->update([
                'full_name'         => $data['full_name'],
                'gender'            => $data['gender'],
                'date_of_birth'     => $data['date_of_birth'] ?? null,
                'phone'             => $data['phone'],
                'email'             => $data['email'],
                'Employee_ID'       => $data['employee_id'],
                'thana_id'          => (int)$data['thana_id'],
                'person_type'       => $data['person_type'],
                'profession'        => (int)$data['profession'],
                'present_address'   => $data['present_address'],
                'updated_by'        => $uid,
                'updated_at'        => now(),
            ]);

            DB::table('education_background')->where('Company_id',$companyId)->where('registration_id',$row->id)->delete();
            foreach ($edu as $r) {
                DB::table('education_background')->insert([
                    'Company_id'      => $companyId,
                    'registration_id' => $row->id,
                    'degree_id'       => (int)$r['degree_id'],
                    'Institution'     => $r['institution'],
                    'Passing_Year'    => (int)$r['passing_year'],
                    'Result_Type'     => $r['result_type'],
                    'obtained_grade_or_score' => $r['score'],
                    'Out_of'          => (int)$r['out_of'],
                    'status'          => 1,
                    'created_by'      => $uid,
                    'updated_by'      => $uid,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            DB::table('job_experiences')->where('Company_id',$companyId)->where('registration_id',$row->id)->delete();
            foreach ($job as $r) {
                DB::table('job_experiences')->insert([
                    'Company_id'      => $companyId,
                    'registration_id' => $row->id,
                    'Employer'        => $r['employer'],
                    'Job_title'       => $r['job_title'],
                    'Joining_date'    => $r['join_date'],
                    'End_date'        => ($r['is_present'] ?? 'N') === 'Y' ? null : ($r['end_date'] ?? null),
                    'is_present_job'  => ($r['is_present'] ?? 'N') === 'Y' ? 'Y' : 'N',
                    'status'          => 1,
                    'created_by'      => $uid,
                    'updated_by'      => $uid,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            DB::table('expertise_on_softwares')->where('Company_id',$companyId)->where('registration_id',$row->id)->delete();
            foreach ($softwareIds as $sid) {
                DB::table('expertise_on_softwares')->insert([
                    'Company_id'          => $companyId,
                    'registration_id'     => $row->id,
                    'expert_on_software'  => $sid,
                    'experience_in_years' => $yearsMap[$sid] ?? '0.0',
                    'status'              => 1,
                    'created_by'          => $uid,
                    'updated_by'          => $uid,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            DB::table('person_skills')->where('Company_id',$companyId)->where('registration_id',$row->id)->delete();
            foreach ($skillIds as $skid) {
                DB::table('person_skills')->insert([
                    'Company_id'      => $companyId,
                    'registration_id' => $row->id,
                    'skill'           => $skid,
                    'status'          => 1,
                    'created_by'      => $uid,
                    'updated_by'      => $uid,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            DB::table('preffered_area_of_job')->where('Company_id',$companyId)->where('registration_id',$row->id)->delete();
            foreach ($taskIds as $tid) {
                DB::table('preffered_area_of_job')->insert([
                    'Company_id'      => $companyId,
                    'registration_id' => $row->id,
                    'Task_Param_ID'   => $tid,
                    'status'          => 1,
                    'created_by'      => $uid,
                    'updated_by'      => $uid,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            DB::table('training_required')->where('Company_id',$companyId)->where('registration_id',$row->id)->delete();
            foreach ($train as $r) {
                DB::table('training_required')->insert([
                    'Company_id'          => $companyId,
                    'registration_id'     => $row->id,
                    'Training_Category_Id'=> (int)$r['category_id'],
                    'Training_ID'         => (int)$r['training_id'],
                    'status'              => 1,
                    'created_by'          => $uid,
                    'updated_by'          => $uid,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            $this->logActivity('update', $uid, $companyId, ['type'=>'company_officer','reg_id'=>$row->id]);
        });

        DB::afterCommit(function () use ($uid, $companyRow, $row, $data, $plan) {
            $photoRel = $this->persistSingleImage($companyRow, $uid, 'photo', $plan['photo'][0], $plan['photo'][1], $plan['photo'][2]);
            $nidFRel  = $this->persistSingleImage($companyRow, $uid, 'nid_front', $plan['nid_front'][0], $plan['nid_front'][1], $plan['nid_front'][2]);
            $nidBRel  = $this->persistSingleImage($companyRow, $uid, 'nid_back',  $plan['nid_back'][0],  $plan['nid_back'][1],  $plan['nid_back'][2]);
            $bcRel    = $this->persistSingleImage($companyRow, $uid, 'birth_certificate', $plan['birth_certificate'][0], $plan['birth_certificate'][1], $plan['birth_certificate'][2]);

            DB::table('registration_master')->where('id',$row->id)->update([
                'Photo'                    => $photoRel ?: $row->Photo,
                'NID'                      => $data['nid_number'] ?? $row->NID,
                'NID_Photo_Front_Page'     => $nidFRel ?: $row->NID_Photo_Front_Page,
                'NID_Photo_Back_Page'      => $nidBRel ?: $row->NID_Photo_Back_Page,
                'Birth_Certificate_Photo'  => $bcRel ?: $row->Birth_Certificate_Photo,
                'updated_at'               => now(),
            ]);
        });

    } catch (QueryException $e) {
        try {
            \Log::error('CompanyOfficer.update QueryException', [
                'sql'       => method_exists($e, 'getSql') ? $e->getSql() : null,
                'bindings'  => method_exists($e, 'getBindings') ? $e->getBindings() : null,
                'msg'       => $e->getMessage(),
                'errorInfo' => $e->errorInfo ?? null,
                'code'      => $e->errorInfo[1] ?? null,
            ]);
        } catch (\Throwable $ignore) {}

        $msg  = $e->getMessage();
        $code = $e->errorInfo[1] ?? null;

        if (str_contains($msg, 'uq_reg_software') || ($code === 1062 && str_contains($msg, 'expertise_on_softwares'))) {
            return $this->backWithInputAndTemp($request, ['software_ids' => 'Duplicate software entry detected for this registration.']);
        }
        if (str_contains($msg, 'experience_in_years') && ($code === 1265 || $code === 1366 || str_contains($msg,'Data truncated'))) {
            return $this->backWithInputAndTemp($request, ['software_years.*' => 'Years must be a number like 2 or 2.3 (max one decimal).']);
        }

        if ($code === 1452 || str_contains($msg, 'foreign key constraint')) {
            if (str_contains($msg, 'person_skills') || str_contains($msg, 'skills') || str_contains($msg, 'fk_ps_skill')) {
                return $this->backWithInputAndTemp($request, ['skill_ids' => 'Failed to save skills. Please reselect valid skills.']);
            }
            if (str_contains($msg, 'preffered_area_of_job') || str_contains($msg, 'tasks_param') || str_contains($msg, 'Task_Param_ID')) {
                return $this->backWithInputAndTemp($request, ['task_ids' => 'Failed to save preferred areas. Please reselect valid items.']);
            }
            if (str_contains($msg, 'expertise_on_softwares') || str_contains($msg, 'software_list') || str_contains($msg, 'expert_on_software')) {
                return $this->backWithInputAndTemp($request, ['software_ids' => 'Failed to save software experience. Please reselect software.']);
            }
            return $this->backWithInputAndTemp($request, ['_e' => 'A reference failed while saving details. Check your selections.']);
        }

        if (str_contains($msg, 'uq_reg_company_phone') || str_contains($msg, 'phone')) {
            return $this->backWithInputAndTemp($request, ['phone' => 'This mobile number is already registered for this company.']);
        }
        if (str_contains($msg, 'uq_reg_company_email') || str_contains($msg, 'email')) {
            return $this->backWithInputAndTemp($request, ['email' => 'This email is already registered for this company.']);
        }
        if (str_contains($msg, 'Passing_Year')) {
            return $this->backWithInputAndTemp($request, ['edu.0.passing_year' => 'Passing year cannot be in the future.']);
        }

        return $this->backWithInputAndTemp($request, ['_e' => 'Unable to update. Please correct highlighted fields and try again.']);
    }

    session()->forget('_temp_uploads');

    $slugOrId = $companyRow->slug ?? $companyRow->id;
    return redirect()->route('backend.company.dashboard.index', ['company' => $slugOrId])
        ->with('status','Registration updated.');
}

    /* -------- AJAX: dependent dropdowns ---------- */

    public function districtsByDivision(Request $request, $company)
    {
        $divisionId = (int)$request->query('division_id', 0);
        if (!$divisionId) return response()->json([]);
        $rows = DB::table('districts')
            ->where('division_id',$divisionId)
            ->orderBy('short_code')->get(['id','name','short_code']);
        return response()->json($rows);
    }

    public function upazilasByDistrict(Request $request, $company)
    {
        $districtId = (int)$request->query('district_id', 0);
        if (!$districtId) return response()->json([]);
        $rows = DB::table('upazilas')
            ->where('district_id',$districtId)
            ->orderBy('name')->get(['id','name','short_code']);
        return response()->json($rows);
    }

    public function thanasByDistrict(Request $request, $company)
    {
        $districtId = (int)$request->query('district_id', 0);
        if (!$districtId) return response()->json([]);
        $rows = DB::table('thanas')
            ->where('district_id',$districtId)
            ->orderBy('name')->get(['id','name','short_code']);
        return response()->json($rows);
    }

    public function trainingsByCategory(Request $request, $company)
    {
        $companyRow = $this->resolveCompany($company);
        if (!$companyRow) return response()->json([]);

        $categoryId = (int)$request->query('category_id', 0);
        if (!$categoryId) return response()->json([]);

        $query = DB::table('training_setup')
            ->selectRaw('Training_ID as id, Training_Name as name')
            ->where('Company_id', (int)$companyRow->id)
            ->where('Training_Category_Id', $categoryId)
            ->where('status', 1)
            ->orderBy('Training_Name');

        try {
            $rows = $query->get();
        } catch (\Throwable $e) {
            $rows = DB::table('training_list')
                ->selectRaw('Training_ID as id, Training_Name as name')
                ->where('Company_id', (int)$companyRow->id)
                ->where('Training_Category_Id', $categoryId)
                ->where('status', 1)
                ->orderBy('Training_Name')
                ->get();
        }

        return response()->json($rows);
    }

    /* -------- AJAX: temp uploads for images -------- */

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
        if (!in_array($field, ['photo','nid_front','nid_back','birth_certificate'], true)) {
            return response()->json(['ok' => false, 'message' => 'Invalid field'], 400);
        }

        if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
            return response()->json(['ok' => false, 'message' => 'No valid file uploaded'], 400);
        }

        $file = $request->file('file');
        $maxBytes = $this->maxImageKB() * 1024;
        if (method_exists($file, 'getSize') && $file->getSize() > $maxBytes) {
            return response()->json(['ok' => false, 'message' => "File too large (max {$this->maxImageKB()}KB)"], 400);
        }

        $ext  = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $name = 'tmp_'.Str::random(10).'_'.time().'.'.$ext;
        $path = $file->storeAs('public/tmp/registration', $name);
        $url  = Storage::url($path);

        $temps = session('_temp_uploads', []);
        $temps["temp_{$field}"] = $url;
        session(['_temp_uploads' => $temps]);

        return response()->json(['ok' => true, 'field' => $field, 'url' => $url]);
    }
}
