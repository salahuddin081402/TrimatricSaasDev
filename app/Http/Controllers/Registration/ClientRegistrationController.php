<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class ClientRegistrationController extends Controller
{
    private function currentUserId(): ?int
    {
        $forced = config('header.dev_force_user_id');

        // If dev forced user id is set, ALWAYS use it for this module.
        if (is_numeric($forced)) {
            return (int) $forced;
        }

        return Auth::id();
    }

    /** Resolve company by {company} route param (slug or model); fallback via user/role if needed */
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

    public function create(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');

        // Only Guest (role_id=10) & no prior registration
        $user = DB::table('users')->where('id',$uid)->first();
        abort_if(!$user, 403, 'No user');
        abort_if((int)$user->role_id !== 10, 403, 'Only Guest can register');

        $has = DB::table('registration_master')
            ->where('user_id',$uid)
            ->where('company_id',$companyRow->id)
            ->whereNull('deleted_at')
            ->exists();
        abort_if($has, 403, 'Already registered');

        $prefillName  = trim(($user->name ?? '') ?: ($user->full_name ?? ''));
        $prefillEmail = $user->email ?? '';

        $divisions = DB::table('divisions')
            ->orderBy('short_code')->get(['id','name','short_code']);

        return view('registration.client.create', [
            'title'     => "{$companyRow->name} | Client Registration",
            'company'   => $companyRow,
            'uid'       => $uid,
            'prefill'   => ['full_name'=>$prefillName, 'email'=>$prefillEmail],
            'divisions' => $divisions,
        ]);
    }

    public function store(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int)$companyRow->id;

        $exists = DB::table('registration_master')
            ->where('user_id',$uid)->where('company_id',$companyId)
            ->whereNull('deleted_at')->exists();
        abort_if($exists, 403, 'Already registered');

        // Validation rules + custom messages
        $rules = [
            'full_name'       => ['required','string','max:150'],
            'gender'          => ['required', Rule::in(['male','female','other'])],
            'date_of_birth'   => ['nullable','date','before:today'],
            'phone'           => array_merge(
                ['required','string','max:30'],
                $this->bdPhoneRule(),
                [
                    Rule::unique('registration_master','phone')
                        ->where(fn($q) => $q->where('company_id',$companyId)->whereNull('deleted_at')),
                ]
            ),
            'email'           => [
                'nullable','email','max:150',
                Rule::unique('registration_master','email')
                    ->where(fn($q) => $q->where('company_id',$companyId)->whereNull('deleted_at')),
            ],
            'division_id'     => ['required','integer','exists:divisions,id'],
            'district_id'     => ['required','integer','exists:districts,id'],
            'upazila_id'      => ['required','integer','exists:upazilas,id'],
            'thana_id'        => ['required','integer','exists:thanas,id'], // MANDATORY
            'person_type'     => ['required', Rule::in(['J','B','H','S','P','O'])],
            'present_address' => ['nullable','string','max:255'],
            'notes'           => ['nullable','string','max:255'],
        ];

        $messages = [
            'phone.unique' => 'This mobile number is already registered for this company.',
            'email.unique' => 'This email is already registered for this company.',
            'phone.regex'  => 'Enter a valid Bangladesh mobile number (01XXXXXXXXX).',
        ];

        $data = $request->validate($rules, $messages);

        try {
            DB::transaction(function () use ($uid, $companyId, $data) {
                DB::table('registration_master')->insert([
                    'company_id'        => $companyId,
                    'user_id'           => $uid,
                    'registration_type' => 'client',
                    'full_name'         => $data['full_name'],
                    'gender'            => $data['gender'],
                    'date_of_birth'     => $data['date_of_birth'] ?? null,
                    'phone'             => $data['phone'],
                    'email'             => $data['email'] ?? null,
                    'division_id'       => (int)$data['division_id'],
                    'district_id'       => (int)$data['district_id'],
                    'upazila_id'        => (int)$data['upazila_id'],
                    'thana_id'          => (int)$data['thana_id'], // REQUIRED
                    'person_type'       => $data['person_type'],
                    'present_address'   => $data['present_address'] ?? null,
                    'notes'             => $data['notes'] ?? null,
                    'status'            => 1,
                    'approval_status'   => 'approved',
                    'created_by'        => $uid,
                    'updated_by'        => $uid,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                // Update user: role -> 9 (Client) and FINAL NAME -> users.name
                DB::table('users')->where('id',$uid)->update([
                    'role_id'    => 9,
                    'name'       => $data['full_name'],
                    'updated_at' => now(),
                ]);

                $this->logActivity('create', $uid, $companyId, ['type'=>'client']);
            });
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'uq_reg_company_phone') || str_contains($msg, 'phone')) {
                return back()->withErrors(['phone' => 'This mobile number is already registered for this company.'])
                             ->withInput();
            }
            if (str_contains($msg, 'uq_reg_company_email') || str_contains($msg, 'email')) {
                return back()->withErrors(['email' => 'This email is already registered for this company.'])
                             ->withInput();
            }
            throw $e;
        }

        $slugOrId = $companyRow->slug ?? $companyRow->id;
        return redirect()->route('backend.company.dashboard.index', ['company' => $slugOrId])
            ->with('status','Client registration completed. Welcome!');
    }

    public function edit(Request $request, $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int)$companyRow->id;

        $row = DB::table('registration_master')
            ->where('company_id',$companyId)->where('user_id',$uid)
            ->whereNull('deleted_at')->orderByDesc('id')->first();
        abort_if(!$row, 404, 'Registration not found');

        $division = DB::table('divisions')->where('id',$row->division_id)->first(['short_code','name']);
        $district = DB::table('districts')->where('id',$row->district_id)->first(['short_code','name']);
        $upazila  = DB::table('upazilas')->where('id',$row->upazila_id)->first(['short_code','name']);

        return view('registration.client.edit', [
            'title'    => "{$companyRow->name} | Edit Client Registration",
            'company'  => $companyRow,
            'row'      => $row,
            'division' => $division,
            'district' => $district,
            'upazila'  => $upazila,
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
            ->whereNull('deleted_at')->orderByDesc('id')->first();
        abort_if(!$row, 404, 'Registration not found');

        // Validation with uniqueness scoped to company and ignoring current row
        $rules = [
            'full_name'       => ['required','string','max:150'],
            'gender'          => ['required', Rule::in(['male','female','other'])],
            'date_of_birth'   => ['nullable','date','before:today'],
            'phone'           => array_merge(
                ['required','string','max:30'],
                $this->bdPhoneRule(),
                [
                    Rule::unique('registration_master','phone')
                        ->where(fn($q) => $q->where('company_id',$companyId)->whereNull('deleted_at'))
                        ->ignore($row->id, 'id'),
                ]
            ),
            'email'           => [
                'nullable','email','max:150',
                Rule::unique('registration_master','email')
                    ->where(fn($q) => $q->where('company_id',$companyId)->whereNull('deleted_at'))
                    ->ignore($row->id, 'id'),
            ],
            'person_type'     => ['required', Rule::in(['J','B','H','S','P','O'])],
            'present_address' => ['nullable','string','max:255'],
            'notes'           => ['nullable','string','max:255'],
            'thana_id'        => ['required','integer','exists:thanas,id'], // MANDATORY
        ];
        $messages = [
            'phone.unique' => 'This mobile number is already registered for this company.',
            'email.unique' => 'This email is already registered for this company.',
            'phone.regex'  => 'Enter a valid Bangladesh mobile number (01XXXXXXXXX).',
        ];

        $data = $request->validate($rules, $messages);

        try {
            DB::transaction(function () use ($uid, $row, $data, $companyId) {
                DB::table('registration_master')->where('id',$row->id)->update([
                    'full_name'       => $data['full_name'],
                    'gender'          => $data['gender'],
                    'date_of_birth'   => $data['date_of_birth'] ?? null,
                    'phone'           => $data['phone'],
                    'email'           => $data['email'] ?? null,
                    'person_type'     => $data['person_type'],
                    'present_address' => $data['present_address'] ?? null,
                    'notes'           => $data['notes'] ?? null,
                    'thana_id'        => (int)$data['thana_id'], // REQUIRED
                    'updated_by'      => $uid,
                    'updated_at'      => now(),
                ]);

                // Persist FINAL NAME back to users table (email remains unchanged)
                DB::table('users')->where('id',$uid)->update([
                    'name'       => $data['full_name'],
                    'updated_at' => now(),
                ]);

                $this->logActivity('update', $uid, $companyId, ['type'=>'client']);
            });
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'uq_reg_company_phone') || str_contains($msg, 'phone')) {
                return back()->withErrors(['phone' => 'This mobile number is already registered for this company.'])
                             ->withInput();
            }
            if (str_contains($msg, 'uq_reg_company_email') || str_contains($msg, 'email')) {
                return back()->withErrors(['email' => 'This email is already registered for this company.'])
                             ->withInput();
            }
            throw $e;
        }

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

    /** NEW: thanas by district (same pattern as upazilas) */
    public function thanasByDistrict(Request $request, $company)
    {
        $districtId = (int)$request->query('district_id', 0);
        if (!$districtId) return response()->json([]);
        $rows = DB::table('thanas')
            ->where('district_id',$districtId)
            ->orderBy('name')->get(['id','name','short_code']);
        return response()->json($rows);
    }
}
