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

class ProfessionalController extends Controller
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

        if (is_numeric($routeCompany)) {
            return DB::table('companies')
                ->where('id',$routeCompany)->where('status',1)->whereNull('deleted_at')->first();
        }

        if (is_string($routeCompany)) {
            $c = DB::table('companies')
                ->where('slug',$routeCompany)->where('status',1)->whereNull('deleted_at')->first();
            if ($c) return $c;
        }

        $uid = $this->currentUserId();
        if ($uid) {
            $user = DB::table('users')->where('id',$uid)->first();
            if ($user && $user->company_id) {
                return DB::table('companies')
                    ->where('id',$user->company_id)->where('status',1)->whereNull('deleted_at')->first();
            }
            if ($user && $user->role_id) {
                return DB::table('companies')
                    ->where('status',1)->whereNull('deleted_at')->orderBy('id')->first();
            }
        }
        return null;
    }

    /** Match CompanyOfficer config */
    private function maxImageKB(): int
    {
        return (int) config('registration.max_image_kb', 1024);
    }

    /** Bangladesh mobile regex */
    private function bdPhoneRule(): array
    {
        return ['required','regex:/^(?:\+?88)?01[3-9]\d{8}$/'];
    }

    /** NID regex from config */
    private function nidRegexFromConfig(): string
    {
        $lengths = (array) config('registration.allowed_nid_lengths', [10,13,17]);
        $parts = array_map(fn($n)=>"\d{{$n}}", $lengths);
        return '/^(?:'.implode('|',$parts).')$/';
    }

    /* ======== PUBLIC (non-Storage) IMAGE PATHS — align with CompanyOfficer ======== */

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
            // $tempUrl is a web path like /storage/tmp/registration/...
            $srcPath = public_path(ltrim(parse_url($tempUrl, PHP_URL_PATH) ?? '', '/'));
            @copy($srcPath, $finalAbs);
            @unlink($srcPath);
        }

        return $finalRel;
    }

    /* ======== TEMP UPLOAD HELPERS (mirror CompanyOfficer) ======== */

    private function mergedTempUploads(Request $request): array
    {
        $sessionTemps = (array) session('_temp_uploads', []);
        $hiddenTemps = [];
        foreach (['photo','nid_front','nid_back','birth_certificate'] as $f) {
            $key = "temp_{$f}";
            $val = trim((string) $request->input($key, ''));
            if ($val !== '') $hiddenTemps[$key] = $val;
        }
        $merged = array_filter($sessionTemps, fn($v)=>$v !== null && $v !== '');
        foreach ($hiddenTemps as $k=>$v) { $merged[$k] = $v; }
        return $merged;
    }

    private function backWithInputAndTemp(Request $request, array $errors)
    {
        $temps = $this->mergedTempUploads($request);
        return back()->withErrors($errors)->withInput()->with('_temp_uploads', $temps);
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
            \Log::warning('Professional.logActivity', ['err'=>$e->getMessage()]);
        }
    }

    /* ======== AJAX: GEO (plain arrays to match blades) ======== */

    public function districtsByDivision(Request $request, $company)
    {
        $divisionId = (int) $request->query('division_id', 0);
        $rows = DB::table('districts')
            ->where('division_id',$divisionId)
            ->orderBy('name')
            ->get(['id','name','short_code']);
        return response()->json($rows->values());
    }

    public function upazilasByDistrict(Request $request, $company)
    {
        $districtId = (int) $request->query('district_id', 0);
        $rows = DB::table('upazilas')
            ->where('district_id',$districtId)
            ->orderBy('name')
            ->get(['id','name','short_code']);
        return response()->json($rows->values());
    }

    public function thanasByDistrict(Request $request, $company)
    {
        $districtId = (int) $request->query('district_id', 0);
        $rows = DB::table('thanas')
            ->where('district_id',$districtId)
            ->orderBy('name')
            ->get(['id','name','short_code']);
        return response()->json($rows->values());
    }

    public function trainingsByCategory(Request $request, $company)
    {
        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');

        $catId = (int) $request->query('category_id', 0);
        $rows = DB::table('training_list')
            ->where('Company_id',$companyRow->id)
            ->where('Training_Category_Id',$catId)
            ->where('status',1)
            ->orderBy('Training_Name')
            ->get(['Training_ID as id','Training_Name as name']);
        return response()->json($rows->values());
    }

    /* ======== TEMP UPLOAD (mirror CompanyOfficer; returns {ok:true,url}) ======== */

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
        $url  = Storage::url($path); // /storage/tmp/registration/...

        $temps = session('_temp_uploads', []);
        $temps["temp_{$field}"] = $url;
        session(['_temp_uploads' => $temps]);

        return response()->json(['ok' => true, 'field' => $field, 'url' => $url]);
    }

    /* ======== CREATE (no reg_key) ======== */

    public function create(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');

        // preload lists
        $divisions   = DB::table('divisions')->orderBy('short_code')->get(['id','name','short_code']);
        $degrees     = DB::table('degrees')->where('is_active',1)->orderBy('short_code')->get(['id','short_code','name']);
        $softwares   = DB::table('software_list')->where('status',1)->orderBy('software_name')->get(['id','software_name']);
        $skills      = DB::table('skills')->where('status',1)->orderBy('skill')->get(['id','skill']);
        $tasks       = DB::table('tasks_param')->where('status',1)->orderBy('Task_Param_Name')->get(['Task_Param_ID','Task_Param_Name']);
        $trainCats   = DB::table('training_category')->where('Company_id',$companyRow->id)->where('status',1)->orderBy('Training_Category_Name')->get(['Training_Category_Id','Training_Category_Name']);
        $professions = DB::table('professions')->where('status',1)->orderBy('profession')->get(['id','profession']);

        // user for locked fields
        $user = DB::table('users')->where('id', $uid)->first(['name','email']);
        abort_if(!$user, 403, 'User not found');

        $ctx = [
            'name'  => $user->name ?: '',
            'email' => $user->email ?: '',
        ];

        return view('registration.professional.create', [
            'title'       => "{$companyRow->name} | Professional Registration",
            'company'     => $companyRow,
            'divisions'   => $divisions,
            'degrees'     => $degrees,
            'softwares'   => $softwares,
            'skills'      => $skills,
            'tasks'       => $tasks,
            'trainCats'   => $trainCats,
            'professions' => $professions,
            'nidLengths'  => (array) config('registration.allowed_nid_lengths', [10,13,17]),
            'tempUploads' => session('_temp_uploads', []),
            'ctx'         => $ctx,
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

        $allowedMimes = 'jpg,jpeg,png,webp,gif,jfif,bmp';
        $maxKB        = $this->maxImageKB();

        $rules = [
            'gender'        => ['required', Rule::in(['male','female','other'])],
            'date_of_birth' => ['nullable','date','before:today'],

            'phone' => array_merge(
                $this->bdPhoneRule(),
                [
                    Rule::unique('registration_master','phone')
                        ->where(fn($q) => $q->where('company_id',$companyId)),
                ]
            ),

            // Geo with parent-child constraints
            'division_id' => ['required','integer','exists:divisions,id'],
            'district_id' => [
                'required','integer',
                Rule::exists('districts','id')->where(
                    fn($q)=>$q->where('division_id', $request->input('division_id'))
                ),
            ],
            'upazila_id' => [
                'required','integer',
                Rule::exists('upazilas','id')->where(
                    fn($q)=>$q->where('district_id', $request->input('district_id'))
                ),
            ],
            'thana_id' => [
                'required','integer',
                Rule::exists('thanas','id')->where(
                    fn($q)=>$q->where('district_id', $request->input('district_id'))
                ),
            ],

            'person_type'     => ['required', Rule::in(['J','B','H','S','P','O'])],
            'profession'      => ['required','integer','exists:professions,id'],
            'present_address' => ['required','string','max:255'],

            'photo'            => ['nullable','file','mimes:'.$allowedMimes,'max:'.$maxKB],
            'nid_number'       => ['nullable','string','regex:'.$this->nidRegexFromConfig()],
            'nid_front'        => ['nullable','file','mimes:'.$allowedMimes,'max:'.$maxKB],
            'nid_back'         => ['nullable','file','mimes:'.$allowedMimes,'max:'.$maxKB],
            'birth_certificate'=> ['nullable','file','mimes:'.$allowedMimes,'max:'.$maxKB],

            'skill_ids'    => ['required','array','min:1'],
            'skill_ids.*'  => ['integer','exists:skills,id'],

            'task_ids'     => ['nullable','array'],
            'task_ids.*'   => ['integer','exists:tasks_param,Task_Param_ID'],

            'software_ids'   => ['nullable','array'],
            'software_ids.*' => ['integer','exists:software_list,id'],
            'software_years' => ['nullable','array'],

            'temp_photo'             => ['nullable','string'],
            'temp_nid_front'         => ['nullable','string'],
            'temp_nid_back'          => ['nullable','string'],
            'temp_birth_certificate' => ['nullable','string'],
        ];


        if (trim((string)$request->input('nid_number','')) !== '') {
            $rules['nid_number'][] =
                Rule::unique('registration_master','NID')
                    ->where(fn($q) => $q->where('company_id',$companyId));
        }

        $messages = [
            'gender.required'    => 'Please select gender.',
            'phone.unique'       => 'This mobile number is already registered for this company.',
            'phone.regex'        => 'Enter a valid Bangladesh mobile number (01XXXXXXXXX).',
            'nid_number.regex'   => 'NID length incorrect.',
            'nid_number.unique'  => 'Same NID already used in this Company.',
            'skill_ids.required' => 'Select at least one skill.',
            'profession.required'=> 'Please select your profession.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->backWithInputAndTemp($request, $validator->errors()->toArray());
        }
        $data = $validator->validated();

        // Normalize arrays
        $skillIds = collect($request->input('skill_ids', []))
            ->map(fn($v)=>(int)$v)->filter(fn($v)=>$v>0)->unique()->values();

        if ($skillIds->isEmpty()) {
            return $this->backWithInputAndTemp($request, ['skill_ids' => 'Select at least one valid skill.']);
        }

        $taskIds = collect($request->input('task_ids', []))
            ->map(fn($v)=>(int)$v)->filter(fn($v)=>$v>0)->unique()->values();

        $hasPhoto = $request->hasFile('photo') || filled($data['temp_photo'] ?? null);
        if (!$hasPhoto) {
            return $this->backWithInputAndTemp($request, ['photo' => 'Photo image is required.']);
        }

        $hasNid = filled($data['nid_number'] ?? null);
        if ($hasNid) {
            $hasNidFront = $request->hasFile('nid_front') || filled($data['temp_nid_front'] ?? null);
            $hasNidBack  = $request->hasFile('nid_back')  || filled($data['temp_nid_back'] ?? null);
            if (!$hasNidFront || !$hasNidBack) {
                return $this->backWithInputAndTemp($request, ['nid_front' => 'Both front and back images are required when NID number is provided.']);
            }
        } else {
            $hasBirth = $request->hasFile('birth_certificate') || filled($data['temp_birth_certificate'] ?? null);
            if (!$hasBirth) {
                return $this->backWithInputAndTemp($request, ['birth_certificate' => 'Birth certificate image is required if NID is not provided.']);
            }
        }

        // Education rows (keys from blade: passing_year)
        $edu = collect($request->input('edu', []))
            ->filter(fn($r) =>
                !empty($r['degree_id']) ||
                !empty($r['institution']) ||
                !empty($r['passing_year']) ||
                !empty($r['result_type']) ||
                !empty($r['score']) ||
                !empty($r['out_of'])
            )->values();

        $dupEdu = [];
        foreach ($edu as $i => $rowData) {
            $r = array_map(fn($v)=> is_string($v)?trim($v):$v, (array)$rowData);
            if (empty($r['degree_id']) || empty($r['institution']) || empty($r['passing_year']) || empty($r['result_type']) || empty($r['score']) || empty($r['out_of'])) {
                return $this->backWithInputAndTemp($request, ["edu.$i.degree_id" => 'Degree, Institution, Passing Year, Result Type, Score, and Out of are required for each education row.']);
            }
            if (!preg_match('/^\d{4}$/', (string)$r['passing_year']) || (int)$r['passing_year'] > (int)date('Y')) {
                return $this->backWithInputAndTemp($request, ["edu.$i.passing_year" => 'Passing year is invalid.']);
            }
            $key = ((int)$r['degree_id']).'|'.Str::lower($r['institution']).'|'.(int)$r['passing_year'];
            if (!empty($dupEdu[$key])) {
                return $this->backWithInputAndTemp($request, ["edu.$i.degree_id" => 'Duplicate education row detected.']);
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
                return $this->backWithInputAndTemp($request, ["job.$i.employer" => 'Employer, Job Title, Joining date and Present-job flag are required in each job row.']);
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

            $key = Str::lower($r['employer']).'|'.Str::lower($r['job_title']).'|'.($r['join_date']).'|'.(($r['is_present']??'N')==='Y'?'P':'F').($r['end_date']??'-');
            if (!empty($dupJob[$key])) {
                return $this->backWithInputAndTemp($request, ["job.$i.employer" => 'Duplicate job row detected.']);
            }
            $dupJob[$key] = true;
        }

        // Software map build
        $softwareIds = collect($request->input('software_ids', []))
            ->map(fn($v)=> is_numeric($v)?(int)$v:null)
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
        foreach ($yearsMap as $sid => $yr) {
            $v = (string) $yr;
            if ($v === '' || $v === null) { $yearsMap[$sid] = '0.0'; continue; }
            if (!preg_match('/^\d+(\.\d)?$/', $v)) {
                $softErrors["software_years.$sid"] = 'Experience must be a number with up to 1 decimal place.';
                continue;
            }
            $num = (float)$v;
            if ($num < 0 || $num > 60) {
                $softErrors["software_years.$sid"] = 'Experience must be between 0 and 60.';
                continue;
            }
            $yearsMap[$sid] = number_format($num, 1, '.', '');
        }
        if (!empty($softErrors)) {
            return $this->backWithInputAndTemp($request, $softErrors);
        }

        // Temp upload plan
        $plan = [
            'photo' => [$request->file('photo'), $data['temp_photo'] ?? null],
            'nid_front' => [$request->file('nid_front'), $data['temp_nid_front'] ?? null],
            'nid_back'  => [$request->file('nid_back'),  $data['temp_nid_back'] ?? null],
            'birth_certificate' => [$request->file('birth_certificate'), $data['temp_birth_certificate'] ?? null],
        ];

        // Fetch user for full_name and email. Enforce email uniqueness manually.
        $userRow = DB::table('users')->where('id',$uid)->first();
        if ($userRow && !empty($userRow->email)) {
            $dupEmail = DB::table('registration_master')
                ->where('company_id', $companyId)
                ->where('email', $userRow->email)
                ->exists();
            if ($dupEmail) {
                return $this->backWithInputAndTemp($request, ['email' => 'This email is already registered for this company.']);
            }
        }

        $regId = null;
        try {
            DB::transaction(function () use ($uid, $companyId, $companyRow, &$regId, $data, $edu, $job, $softwareIds, $yearsMap, $skillIds, $taskIds, $plan, $userRow, $request) {

                // 1) Master
                $regId = DB::table('registration_master')->insertGetId([
                    'company_id'        => $companyId,
                    'user_id'           => $uid,
                    'registration_type' => 'professional',
                    'full_name'         => $userRow->name ?? '',
                    'gender'            => $data['gender'],
                    'date_of_birth'     => $data['date_of_birth'] ?? null,
                    'phone'             => $data['phone'],
                    'email'             => $userRow->email ?? null,
                    'division_id'       => (int)$data['division_id'],
                    'district_id'       => (int)$data['district_id'],
                    'upazila_id'        => (int)$data['upazila_id'],
                    'thana_id'          => (int)$data['thana_id'],
                    'person_type'       => $data['person_type'],
                    'Profession'        => (int)$data['profession'],
                    'present_address'   => $data['present_address'],
                    'approval_status'   => 'approved',
                    'status'            => 1,
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
                        'End_date'        => $r['end_date'] ?? null,
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

                foreach (($taskIds ?? collect()) as $tid) {
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

                // Trainings rows
                $train = collect($request->input('train', []))
                    ->filter(fn($r) => !empty($r['category_id']) || !empty($r['training_id']))
                    ->values();

                foreach ($train as $r) {
                    if (empty($r['category_id']) || empty($r['training_id'])) {
                        continue;
                    }
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
            // Promote acting user to role_id=11 inside the same transaction
            if (!empty($uid)) {
                DB::table('users')
                    ->where('id', $uid)
                    ->where('role_id', '!=', 11)
                    ->update([
                        'role_id'    => 11,
                        'updated_at' => now(),
                    ]);
            }
                $this->logActivity('create', $uid, $companyId, ['type'=>'professional','reg_id'=>$regId]);
            });



            // After commit: move files and update paths
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
                \Log::error('Professional.store QueryException', [
                    'sql'       => method_exists($e, 'getSql') ? $e->getSql() : null,
                    'bindings'  => method_exists($e, 'getBindings') ? $e->getBindings() : null,
                    'message'   => $e->getMessage(),
                ]);
            } catch (\Throwable $ignore) {}

            $msg  = $e->getMessage();
            $code = $e->errorInfo[1] ?? 0;

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
                return $this->backWithInputAndTemp($request, ['_error' => 'Reference failed while saving details. Check your selections.']);
            }

            if (str_contains($msg, 'uq_reg_company_phone') || str_contains($msg, 'phone')) {
                return $this->backWithInputAndTemp($request, ['phone' => 'This mobile number is already registered for this company.']);
            }

            if (str_contains($msg, 'uq_reg_company_email') || str_contains($msg, 'email')) {
                return $this->backWithInputAndTemp($request, ['email' => 'This email is already registered for this company.']);
            }

            if (str_contains($msg, 'uq_reg_company_nid') || str_contains($msg, 'NID')) {
                return $this->backWithInputAndTemp($request, ['nid_number' => 'Same NID already used in this Company.']);
            }

            return $this->backWithInputAndTemp($request, ['_error' => 'Failed to save. Please try again.']);
        } catch (\Throwable $e) {
            \Log::error('Professional.store Throwable', ['err'=>$e->getMessage()]);
            return $this->backWithInputAndTemp($request, ['_error' => 'Failed to save. Please try again.']);
        }

        session()->forget('_temp_uploads');

        return redirect()->route('backend.company.dashboard.index', ['company'=>$companyRow->slug])
            ->with('success','Registration done successfully.');
    }

    /* ======== EDIT ======== */

    public function edit(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        // existing professional registration for this user+company
        $row = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $uid)
            ->where('registration_type', 'professional')
            ->orderByDesc('id')
            ->first();
        abort_if(!$row, 404, 'Registration not found');

        // user context for locked fields
        $user = DB::table('users')->where('id', $uid)->first(['name','email']);
        abort_if(!$user, 403, 'User not found');
        $ctx = [
            'name'  => $user->name ?: '',
            'email' => $user->email ?: '',
        ];

        // geo labels
        $division = DB::table('divisions')->where('id', $row->division_id)->first();
        $district = DB::table('districts')->where('id', $row->district_id)->first();
        $upazila  = DB::table('upazilas')->where('id', $row->upazila_id)->first();
        $thana    = DB::table('thanas')->where('id', $row->thana_id)->first();

        // dropdown sources
        $divisions   = DB::table('divisions')->orderBy('short_code')->get(['id','name','short_code']);
        $degrees     = DB::table('degrees')->where('is_active',1)->orderBy('short_code')->get(['id','short_code','name']);
        $softwares   = DB::table('software_list')->where('status',1)->orderBy('software_name')->get(['id','software_name']);
        $skills      = DB::table('skills')->where('status',1)->orderBy('skill')->get(['id','skill']);
        $tasks       = DB::table('tasks_param')->where('status',1)->orderBy('Task_Param_Name')->get(['Task_Param_ID','Task_Param_Name']);
        $trainCats   = DB::table('training_category')->where('Company_id',$companyId)->where('status',1)->orderBy('Training_Category_Name')->get(['Training_Category_Id','Training_Category_Name']);
        $professions = DB::table('professions')->where('status',1)->orderBy('profession')->get(['id','profession']);

        // selected sets
        $selSoft   = DB::table('expertise_on_softwares')->where('Company_id',$companyId)->where('registration_id',$row->id)
                       ->pluck('expert_on_software')->map(fn($v)=>(int)$v)->values();
        $softExp   = DB::table('expertise_on_softwares')->where('Company_id',$companyId)->where('registration_id',$row->id)
                       ->pluck('experience_in_years','expert_on_software')->toArray();
        $selSkills = DB::table('person_skills')->where('Company_id',$companyId)->where('registration_id',$row->id)
                       ->pluck('skill')->map(fn($v)=>(int)$v)->values();
        $selTasks  = DB::table('preffered_area_of_job')->where('Company_id',$companyId)->where('registration_id',$row->id)
                       ->pluck('Task_Param_ID')->map(fn($v)=>(int)$v)->values();

        // detail rows
        $eduRows   = DB::table('education_background')->where('Company_id',$companyId)->where('registration_id',$row->id)
                       ->orderBy('Passing_Year')->get();
        $jobRows   = DB::table('job_experiences')->where('Company_id',$companyId)->where('registration_id',$row->id)
                       ->orderBy('Joining_date')->get();
        $trainRows = DB::table('training_required')->where('Company_id',$companyId)->where('registration_id',$row->id)
                       ->get();

        // convenience props
        $row->profession_id   = $row->Profession ?? null;
        $row->profession_name = $row->Profession ? DB::table('professions')->where('id',$row->Profession)->value('profession') : null;
        $row->division_name   = $division->name ?? null;
        $row->district_name   = $district->name ?? null;
        $row->upazila_name    = $upazila->name ?? null;
        $row->thana_name      = $thana->name ?? null;

        return view('registration.professional.edit', [
            'title'       => "{$companyRow->name} | Edit Professional Registration",
            'company'     => $companyRow,
            'row'         => $row,
            'division'    => $division,
            'district'    => $district,
            'upazila'     => $upazila,
            'thana'       => $thana,

            'divisions'   => $divisions, 
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
            'ctx'         => $ctx,
        ]);
    }

    /* ======== UPDATE ======== */

   public function update(Request $request, $company)
{
    $uid = $this->currentUserId();
    abort_if(!$uid, 403, 'Login required');

    $companyRow = $this->resolveCompany($company);
    abort_if(!$companyRow, 404, 'Company not found');
    $companyId = (int)$companyRow->id;

    $row = DB::table('registration_master')
        ->where('company_id',$companyId)->where('user_id',$uid)
        ->where('registration_type','professional')
        ->orderByDesc('id')->first();
    abort_if(!$row, 404, 'Registration not found');

    $allowedMimes = 'jpg,jpeg,png,webp,gif,jfif,bmp';
    $maxKB        = $this->maxImageKB();

    // Server-side rules = EXACTLY as store(), with uniques ignoring current row
    $rules = [
        'gender'          => ['required', Rule::in(['male','female','other'])],
        'date_of_birth'   => ['nullable','date','before:today'],

        'phone'           => array_merge(
            $this->bdPhoneRule(),
            [
                Rule::unique('registration_master','phone')
                    ->where(fn($q) => $q->where('company_id',$companyId))
                    ->ignore($row->id, 'id'),
            ]
        ),

 
        // Geo with parent-child constraints
        'division_id' => ['required','integer','exists:divisions,id'],
        'district_id' => [
            'required','integer',
            Rule::exists('districts','id')->where(
                fn($q)=>$q->where('division_id', $request->input('division_id'))
            ),
        ],
        'upazila_id' => [
            'required','integer',
            Rule::exists('upazilas','id')->where(
                fn($q)=>$q->where('district_id', $request->input('district_id'))
            ),
        ],
        'thana_id' => [
            'required','integer',
            Rule::exists('thanas','id')->where(
                fn($q)=>$q->where('district_id', $request->input('district_id'))
            ),
        ],

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

        'task_ids'           => ['nullable','array'],
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
                ->where(fn($q) => $q->where('company_id',$companyId))
                ->ignore($row->id, 'id');
    }

    $messages = [
        'gender.required'    => 'Please select gender.',
        'phone.unique'       => 'This mobile number is already registered for this company.',
        'phone.regex'        => 'Enter a valid Bangladesh mobile number (01XXXXXXXXX).',
        'nid_number.regex'   => 'NID length incorrect.',
        'nid_number.unique'  => 'Same NID already used in this Company.',
        'skill_ids.required' => 'Select at least one skill.',
        'profession.required'=> 'Please select your profession.',
    ];

    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
        return $this->backWithInputAndTemp($request, $validator->errors()->toArray());
    }
    $data = $validator->validated();

    // Normalize arrays
    $skillIds = collect($request->input('skill_ids', []))
        ->map(fn($v)=>(int)$v)->filter(fn($v)=>$v>0)->unique()->values();
    if ($skillIds->isEmpty()) {
        return $this->backWithInputAndTemp($request, ['skill_ids' => 'Select at least one valid skill.']);
    }

    $taskIds = collect($request->input('task_ids', []))
        ->map(fn($v)=>(int)$v)->filter(fn($v)=>$v>0)->unique()->values();

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

    $dupEdu = [];
    foreach ($edu as $i => $rowData) {
        $r = array_map(fn($v)=> is_string($v)?trim($v):$v, (array)$rowData);
        if (empty($r['degree_id']) || empty($r['institution']) || empty($r['passing_year']) || empty($r['result_type']) || empty($r['score']) || empty($r['out_of'])) {
            return $this->backWithInputAndTemp($request, ["edu.$i.degree_id" => 'Degree, Institution, Passing Year, Result Type, Score, and Out of are required for each education row.']);
        }
        if (!preg_match('/^\d{4}$/', (string)$r['passing_year']) || (int)$r['passing_year'] > (int)date('Y')) {
            return $this->backWithInputAndTemp($request, ["edu.$i.passing_year" => 'Passing year is invalid.']);
        }
        $key = ((int)$r['degree_id']).'|'.Str::lower($r['institution']).'|'.(int)$r['passing_year'];
        if (!empty($dupEdu[$key])) {
            return $this->backWithInputAndTemp($request, ["edu.$i.degree_id" => 'Duplicate education row detected.']);
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
            return $this->backWithInputAndTemp($request, ["job.$i.employer" => 'Employer, Job Title, Joining date and Present-job flag are required in each job row.']);
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

        $key = Str::lower($r['employer']).'|'.Str::lower($r['job_title']).'|'.($r['join_date']).'|'.(($r['is_present']??'N')==='Y'?'P':'F').($r['end_date']??'-');
        if (!empty($dupJob[$key])) {
            return $this->backWithInputAndTemp($request, ["job.$i.employer" => 'Duplicate job row detected.']);
        }
        $dupJob[$key] = true;
    }

    // Software map build
    $softwareIds = collect($request->input('software_ids', []))
        ->map(fn($v)=> is_numeric($v)?(int)$v:null)
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
    foreach ($yearsMap as $sid => $yr) {
        $v = (string) $yr;
        if ($v === '' || $v === null) { $yearsMap[$sid] = '0.0'; continue; }
        if (!preg_match('/^\d+(\.\d)?$/', $v)) {
            $softErrors["software_years.$sid"] = 'Experience must be a number with up to 1 decimal place.';
            continue;
        }
        $num = (float)$v;
        if ($num < 0 || $num > 60) {
            $softErrors["software_years.$sid"] = 'Experience must be between 0 and 60.';
            continue;
        }
        $yearsMap[$sid] = number_format($num, 1, '.', '');
    }
    if (!empty($softErrors)) {
        return $this->backWithInputAndTemp($request, $softErrors);
    }

    // --- Parity presence checks (consider existing assets) ---
    $hasPhotoExisting = filled($row->Photo);
    $hasPhotoNew      = $request->hasFile('photo') || filled($data['temp_photo'] ?? null);
    if (!$hasPhotoExisting && !$hasPhotoNew) {
        return $this->backWithInputAndTemp($request, ['photo' => 'Photo image is required.']);
    }

    $nidNumberEffective = trim((string)($data['nid_number'] ?? $row->NID ?? ''));
    if ($nidNumberEffective !== '') {
        $hasNidFront = $request->hasFile('nid_front') || filled($data['temp_nid_front'] ?? null) || filled($row->NID_Photo_Front_Page);
        $hasNidBack  = $request->hasFile('nid_back')  || filled($data['temp_nid_back']  ?? null) || filled($row->NID_Photo_Back_Page);
        if (!$hasNidFront || !$hasNidBack) {
            return $this->backWithInputAndTemp($request, ['nid_front' => 'Both front and back images are required when NID number is provided.']);
        }
    } else {
        $hasBirthExisting = filled($row->Birth_Certificate_Photo);
        $hasBirthNew      = $request->hasFile('birth_certificate') || filled($data['temp_birth_certificate'] ?? null);
        if (!$hasBirthExisting && !$hasBirthNew) {
            return $this->backWithInputAndTemp($request, ['birth_certificate' => 'Birth certificate image is required if NID is not provided.']);
        }
    }

    // Temp upload plan with existing paths
    $plan = [
        'photo' => [$request->file('photo'), $data['temp_photo'] ?? null, $row->Photo],
        'nid_front' => [$request->file('nid_front'), $data['temp_nid_front'] ?? null, $row->NID_Photo_Front_Page],
        'nid_back'  => [$request->file('nid_back'),  $data['temp_nid_back'] ?? null,  $row->NID_Photo_Back_Page],
        'birth_certificate' => [$request->file('birth_certificate'), $data['temp_birth_certificate'] ?? null, $row->Birth_Certificate_Photo],
    ];

    try {
        DB::transaction(function () use ($uid, $companyId, $row, $request, $data, $edu, $job, $softwareIds, $yearsMap, $skillIds, $taskIds) {

        DB::table('registration_master')->where('id',$row->id)->update([
            'gender'          => $data['gender'],
            'date_of_birth'   => $data['date_of_birth'] ?? null,
            'phone'           => $data['phone'],
            'division_id'     => (int)$data['division_id'],
            'district_id'     => (int)$data['district_id'],
            'upazila_id'      => (int)$data['upazila_id'],
            'thana_id'        => (int)$data['thana_id'],
            'person_type'     => $data['person_type'],
            'Profession'      => (int)$data['profession'],
            'present_address' => $data['present_address'],
            'updated_by'      => $uid,
            'updated_at'      => now(),
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
                    'End_date'        => $r['end_date'] ?? null,
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
            foreach (($taskIds ?? collect()) as $tid) {
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
            $train = collect($request->input('train', []))
                ->filter(fn($r) => !empty($r['category_id']) || !empty($r['training_id']))
                ->values();
            foreach ($train as $r) {
                if (empty($r['category_id']) || empty($r['training_id'])) {
                    continue;
                }
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

            $this->logActivity('update', $uid, $companyId, ['type'=>'professional','reg_id'=>$row->id]);
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
            \Log::error('Professional.update QueryException', [
                'sql'       => method_exists($e, 'getSql') ? $e->getSql() : null,
                'bindings'  => method_exists($e, 'getBindings') ? $e->getBindings() : null,
                'message'   => $e->getMessage(),
            ]);
        } catch (\Throwable $ignore) {}

        $msg  = $e->getMessage();
        $code = $e->errorInfo[1] ?? 0;

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
            return $this->backWithInputAndTemp($request, ['_error' => 'Reference failed while saving details. Check your selections.']);
        }

        if (str_contains($msg, 'uq_reg_company_phone') || str_contains($msg, 'phone')) {
            return $this->backWithInputAndTemp($request, ['phone' => 'This mobile number is already registered for this company.']);
        }

        if (str_contains($msg, 'uq_reg_company_email') || str_contains($msg, 'email')) {
            return $this->backWithInputAndTemp($request, ['email' => 'This email is already registered for this company.']);
        }

        if (str_contains($msg, 'uq_reg_company_nid') || str_contains($msg, 'NID')) {
            return $this->backWithInputAndTemp($request, ['nid_number' => 'Same NID already used in this Company.']);
        }

        return $this->backWithInputAndTemp($request, ['_error' => 'Failed to update. Please try again.']);
    } catch (\Throwable $e) {
        \Log::error('Professional.update Throwable', ['err'=>$e->getMessage()]);
        return $this->backWithInputAndTemp($request, ['_error' => 'Failed to update. Please try again.']);
    }

    session()->forget('_temp_uploads');

    return redirect()->route('backend.company.dashboard.index', ['company'=>$companyRow->slug])
        ->with('success','Registration Updated successfully.');
}

}
