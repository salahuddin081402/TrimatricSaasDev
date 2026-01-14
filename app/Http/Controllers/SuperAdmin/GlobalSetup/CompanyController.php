<?php

namespace App\Http\Controllers\SuperAdmin\GlobalSetup;

use App\Http\Controllers\Controller;
use App\Models\SuperAdmin\GlobalSetup\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    private ?int $userId = null;
    private ?int $companyId = null;

    /**
     * Must match menus.uri for Companies index in your menus seed.
     */
    private string $menuUri = 'superadmin.globalsetup.companies.index';

    public function __construct()
    {
        // current user (auth or forced)
        $forcedUserId = config('header.dev_force_user_id'); // null or an id
        $this->userId =  $forcedUserId  ?? Auth::id();

        // resolve company_id from user; if missing, fall back to role.company_id
        if ($this->userId) {
            $userCompanyId = DB::table('users')->where('id', $this->userId)->value('company_id');

            if ($userCompanyId) {
                $this->companyId = (int) $userCompanyId;
            } else {
                $roleId = DB::table('users')->where('id', $this->userId)->value('role_id');
                if ($roleId) {
                    $this->companyId = (int) DB::table('roles')->where('id', $roleId)->value('company_id');
                }
            }
        }
    }

    /** List + search + sort + offset/limit pagination */
    public function index(Request $request)
    {
        $this->abortIfNoMenuAccess($this->userId, $this->menuUri);

        $page   = max(1, (int) $request->query('page', 1));
        $limit  = max(1, min(100, (int) $request->query('limit', 10)));
        $search = trim((string) $request->query('search', ''));
        $offset = ($page - 1) * $limit;

        $sort = (string) $request->query('sort', 'id');
        $dir  = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['id', 'name', 'status', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'id';

        $base = DB::table('companies as c')
            ->join('countries as k', 'k.id', '=', 'c.country_id')
            ->whereNull('c.deleted_at')
            ->select('c.id','c.name','c.logo','c.status','c.created_at','k.name as country_name');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $base->where(function ($q) use ($like) {
                $q->where('c.name', 'like', $like)
                  ->orWhere('k.name', 'like', $like);
            });
        }

        $total = (clone $base)->count();

        $rows = (clone $base)
            ->orderBy($sort === 'created_at' ? 'c.created_at' : "c.{$sort}", $dir)
            ->limit($limit)
            ->offset($offset)
            ->get();

        $totalPages = max(1, (int) ceil($total / $limit));
        $winStart   = max(1, $page - 3);
        $winEnd     = min($totalPages, $page + 3);

        $can = $this->actionPermissions($this->userId, $this->menuUri);

        return view('backend.modules.global-setup.companies.index', compact(
            'rows', 'page', 'limit', 'search', 'total', 'totalPages', 'winStart', 'winEnd', 'can', 'sort', 'dir'
        ))->with('title', 'Companies');
    }

    public function create()
    {
        $this->abortIfForbidden('create');

        $countries = DB::table('countries')->whereNull('deleted_at')->orderBy('name')->get(['id','name']);
        return view('backend.modules.global-setup.companies.create', [
            'title'     => 'Add Company',
            'countries' => $countries,
        ]);
    }

    public function store(Request $request)
    {
        $this->abortIfForbidden('create');

        $messages = [
            'country_id.required' => 'Please select a country.',
            'name.required'       => 'Please enter a company name.',
            'status.required'     => 'Please select a status.',
        ];

        $validated = $request->validate([
            'country_id' => ['required','integer','exists:countries,id'],
            'name'       => ['required','string','max:190','unique:companies,name,NULL,id,deleted_at,NULL'],
            'description'=> ['nullable','string','max:1000'],
            'address'    => ['nullable','string','max:500'],
            'contact_no' => ['nullable','string','max:50'],
            'status'     => ['required','in:0,1'],
            'logo'       => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
        ], $messages);

        $data = collect($validated)->except('logo')->toArray();

        $company = new Company($data);
        $company->created_by = $this->userId ?: 0;
        $company->updated_by = $this->userId ?: 0;

        // logo handling
        if ($request->hasFile('logo')) {
            $countryName = DB::table('countries')->where('id', $validated['country_id'])->value('name') ?? 'unknown';
            $countrySlug = Str::slug($countryName);
            $companySlug = Str::slug($validated['name']);

            $folderPath = public_path("assets/images/{$countrySlug}/{$companySlug}/logo");
            if (!is_dir($folderPath)) mkdir($folderPath, 0777, true);

            $file     = $request->file('logo');
            $fileName = "{$companySlug}-logo-".time().'.'.$file->getClientOriginalExtension();
            $file->move($folderPath, $fileName);

            $company->logo = "assets/images/{$countrySlug}/{$companySlug}/logo/{$fileName}";
        }

        $company->save();

        $this->logActivity('add', 'companies', $company->id, [
            'name' => $company->name, 'status' => $company->status
        ]);

        return redirect()->route('superadmin.globalsetup.companies.index')
            ->with('status', 'Company created successfully.');
    }

    public function show($id)
    {
        $this->abortIfForbidden('view');

        $company = Company::findOrFail($id);
        $country = DB::table('countries')->where('id',$company->country_id)->value('name');

        return view('backend.modules.global-setup.companies.show', [
            'title'   => 'View Company',
            'company' => $company,
            'country' => $country,
        ]);
    }

    public function edit($id)
    {
        $this->abortIfForbidden('edit');

        $company   = Company::findOrFail($id);
        $countries = DB::table('countries')->whereNull('deleted_at')->orderBy('name')->get(['id','name']);

        return view('backend.modules.global-setup.companies.edit', [
            'title'     => 'Edit Company',
            'company'   => $company,
            'countries' => $countries,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->abortIfForbidden('edit');

        $company = Company::findOrFail($id);

        $messages = [
            'country_id.required' => 'Please select a country.',
            'name.required'       => 'Please enter a company name.',
            'status.required'     => 'Please select a status.',
        ];

        $validated = $request->validate([
            'country_id' => ['required','integer','exists:countries,id'],
            'name'       => ['required','string','max:190','unique:companies,name,'.$company->id.',id,deleted_at,NULL'],
            'description'=> ['nullable','string','max:1000'],
            'address'    => ['nullable','string','max:500'],
            'contact_no' => ['nullable','string','max:50'],
            'status'     => ['required','in:0,1'],
            'logo'       => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
        ], $messages);

        $data = collect($validated)->except('logo')->toArray();
        $company->fill($data);
        $company->updated_by = $this->userId ?: 0;

        if ($request->hasFile('logo')) {
            $countryName = DB::table('countries')->where('id', $validated['country_id'])->value('name') ?? 'unknown';
            $countrySlug = Str::slug($countryName);
            $companySlug = Str::slug($validated['name']);

            $folderPath = public_path("assets/images/{$countrySlug}/{$companySlug}/logo");
            if (!is_dir($folderPath)) mkdir($folderPath, 0777, true);

            $file     = $request->file('logo');
            $fileName = "{$companySlug}-logo-".time().'.'.$file->getClientOriginalExtension();
            $file->move($folderPath, $fileName);

            $company->logo = "assets/images/{$countrySlug}/{$companySlug}/logo/{$fileName}";
        }

        $company->save();

        $this->logActivity('edit', 'companies', $company->id, [
            'name' => $company->name, 'status' => $company->status
        ]);

        return redirect()->route('superadmin.globalsetup.companies.index')
            ->with('status', 'Company updated successfully.');
    }

    /** AJAX soft delete */
    public function destroy($id)
    {
        $this->abortIfForbidden('delete');

        $company = Company::findOrFail($id);
        $company->updated_by = $this->userId ?: 0;
        $company->save();
        $company->delete();

        $this->logActivity('delete', 'companies', (int) $id, [
            'name' => $company->name
        ]);

        return response()->json(['ok' => true, 'id' => (int) $id]);
    }

    /* ================= RBAC helpers (same pattern you used) ================= */

    private function actionPermissions(?int $userId, string $menuUri): array
    {
        if (!$userId) abort(403, 'Forbidden (no user)');
        $roleId = (int) DB::table('users')->where('id', $userId)->value('role_id');
        if (!$roleId) abort(403, 'Forbidden (no role)');

        $actions = DB::table('role_menu_action_permissions as rmap')
            ->join('menus as m', 'm.id', '=', 'rmap.menu_id')
            ->join('actions as a', 'a.id', '=', 'rmap.action_id')
            ->where('m.uri', $menuUri)
            ->where('rmap.role_id', $roleId)
            ->where('rmap.allowed', 1)
            ->pluck('a.name')
            ->toArray();

        return [
            'view'   => in_array('view', $actions, true),
            'create' => in_array('create', $actions, true),
            'edit'   => in_array('edit', $actions, true),
            'delete' => in_array('delete', $actions, true),
        ];
    }

    private function logActivity(string $action, string $table, int $rowId, array $details = []): void
    {
        DB::table('activity_logs')->insert([
            'company_id' => $this->companyId ?: 0,
            'user_id'    => $this->userId ?: 0,
            'action'     => $action,
            'table_name' => $table,
            'row_id'     => $rowId,
            'details'    => json_encode($details),
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'time_local' => now(),
            'time_dhaka' => now(),
            'created_by' => $this->userId ?: 0,
            'updated_by' => $this->userId ?: 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function abortIfForbidden(string $action): void
    {
        $perm = $this->actionPermissions($this->userId, $this->menuUri);
        if (empty($perm[$action])) abort(403, "Forbidden ({$action})");
    }

    private function abortIfNoMenuAccess(?int $userId, string $menuUri): void
    {
        if (!$userId) abort(403, 'Forbidden (no user)');
        $roleId = (int) DB::table('users')->where('id', $userId)->value('role_id');

        $has = DB::table('role_menu_mappings as rmm')
            ->join('menus as m', 'm.id', '=', 'rmm.menu_id')
            ->where('m.uri', $menuUri)
            ->where('rmm.role_id', $roleId)
            ->whereNull('rmm.deleted_at')
            ->exists();

        if (!$has) abort(403, 'Forbidden (no menu access)');
    }
}
