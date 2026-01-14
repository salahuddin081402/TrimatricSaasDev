<?php

namespace App\Http\Controllers\SuperAdmin\GlobalSetup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SuperAdmin\GlobalSetup\Country;

class CountryController extends Controller
{
    /** Need to swap to Auth later */
    private int $userId;
    private int $companyId;

    /**
     * Route name (menu URI) used for RBAC lookups.
     * Must match the `menus.uri` for Countries index in my Menu table
     */
    private string $menuUri = 'superadmin.globalsetup.countries.index';

    public function __construct()
    {

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
        // NOW (no authentication yet)
     //   $this->userId    = 1; // Super Admin
    //    $this->companyId = 1; // Trimatric Global

        // LATER (after auth):
        // $this->userId    = auth()->id() ?? 1;
        // $this->companyId = auth()->user()->company_id ?? 1;
    }

    /** OFFSET/LIMIT pagination + simple LIKE search (name, short_code) + safe sorting. */
    public function index(Request $request)
    {
        $this->abortIfNoMenuAccess($this->userId, $this->menuUri);

        $page   = max(1, (int) $request->query('page', 1));
        $limit  = max(1, min(100, (int) $request->query('limit', 10))); // cap 100
        $search = trim((string) $request->query('search', ''));
        $offset = ($page - 1) * $limit;

        // Sorting (whitelist + normalize)
        $sort = (string) $request->query('sort', 'id');
        $dir  = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['id', 'name', 'short_code', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $base = DB::table('countries')
            ->select('id', 'name', 'short_code', 'created_at')
            ->whereNull('deleted_at');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $base->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('short_code', 'like', $like);
            });
        }

        $total = (clone $base)->count();

        $rows = (clone $base)
            ->orderBy($sort, $dir)
            ->limit($limit)
            ->offset($offset)
            ->get();

        $totalPages = max(1, (int) ceil($total / $limit));
        $winStart   = max(1, $page - 3);
        $winEnd     = min($totalPages, $page + 3);

        $can = $this->actionPermissions($this->userId, $this->menuUri);

        return view('backend.modules.global-setup.countries.index', compact(
            'rows', 'page', 'limit', 'search', 'total', 'totalPages', 'winStart', 'winEnd', 'can', 'sort', 'dir'
        ))->with('title', 'Countries (Index)');
    }

    public function create()
    {
        $this->abortIfForbidden('create');

        return view('backend.modules.global-setup.countries.create', [
            'title' => 'Add Country'
        ]);
    }

    public function store(Request $request)
    {
        $this->abortIfForbidden('create');

        $messages = [
            'name.required' => 'Please enter a country name.',
            'name.max' => 'Country name may not be greater than :max characters.',
            'name.unique' => 'This country name already exists.',
            'short_code.max' => 'Short code may not be greater than :max characters.'
        ];
        $attributes = [
            'name' => 'Country name',
            'short_code' => 'Short code'
        ];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:countries,name,NULL,id,deleted_at,NULL'],
            'short_code' => ['nullable', 'string', 'max:10']
        ], $messages, $attributes);

        $country = new Country($validated);
        $country->created_by = $this->userId;
        $country->updated_by = $this->userId;
        $country->save();

        $this->logActivity('add', 'countries', $country->id, [
            'name' => $country->name,
            'short_code' => $country->short_code
        ]);

        return redirect()
            ->route('superadmin.globalsetup.countries.index')
            ->with('status', 'Country created successfully.');
    }

    public function show($id)
    {
        $this->abortIfForbidden('view');

        $country = Country::findOrFail($id);

        return view('backend.modules.global-setup.countries.show', [
            'title' => 'View Country',
            'country' => $country
        ]);
    }

    public function edit($id)
    {
        $this->abortIfForbidden('edit');

        $country = Country::findOrFail($id);

        return view('backend.modules.global-setup.countries.edit', [
            'title' => 'Edit Country',
            'country' => $country
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->abortIfForbidden('edit');

        $country = Country::findOrFail($id);

        $messages = [
            'name.required' => 'Please enter a country name.',
            'name.max' => 'Country name may not be greater than :max characters.',
            'name.unique' => 'This country name already exists.',
            'short_code.max' => 'Short code may not be greater than :max characters.'
        ];
        $attributes = [
            'name' => 'Country name',
            'short_code' => 'Short code'
        ];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:countries,name,' . $country->id . ',id,deleted_at,NULL'],
            'short_code' => ['nullable', 'string', 'max:10']
        ], $messages, $attributes);

        $country->fill($validated);
        $country->updated_by = $this->userId;
        $country->save();

        $this->logActivity('edit', 'countries', $country->id, [
            'name' => $country->name,
            'short_code' => $country->short_code
        ]);

        return redirect()
            ->route('superadmin.globalsetup.countries.index')
            ->with('status', 'Country updated successfully.');
    }

    /** AJAX-friendly soft delete */
    public function destroy($id)
    {
        $this->abortIfForbidden('delete');

        $country = Country::findOrFail($id);
        $country->updated_by = $this->userId;
        $country->save();
        $country->delete();

        $this->logActivity('delete', 'countries', (int) $id, [
            'name' => $country->name,
            'short_code' => $country->short_code
        ]);

        return response()->json(['ok' => true, 'id' => (int) $id]);
    }

    /* ================= Helpers ================= */

    private function actionPermissions(int $userId, string $menuUri): array
    {
        $roleId = (int) DB::table('users')->where('id', $userId)->value('role_id');
        if (!$roleId) {
            abort(403, 'Forbidden (no role)');
        }

        $actions = DB::table('role_menu_action_permissions as rmap')
            ->join('menus as m', 'm.id', '=', 'rmap.menu_id')
            ->join('actions as a', 'a.id', '=', 'rmap.action_id')
            ->where('m.uri', $menuUri)
            ->where('rmap.role_id', $roleId)
            ->where('rmap.allowed', 1)
            ->pluck('a.name')
            ->toArray();

        return [
            'view' => in_array('view', $actions, true),
            'create' => in_array('create', $actions, true),
            'edit' => in_array('edit', $actions, true),
            'delete' => in_array('delete', $actions, true)
        ];
    }

    private function logActivity(string $action, string $table, int $rowId, array $details = []): void
    {
        DB::table('activity_logs')->insert([
            'company_id' => $this->companyId,
            'user_id' => $this->userId,
            'action' => $action,
            'table_name' => $table,
            'row_id' => $rowId,
            'details' => json_encode($details),
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'time_local' => now(),
            'time_dhaka' => now(),
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    private function abortIfForbidden(string $action): void
    {
        $perm = $this->actionPermissions($this->userId, $this->menuUri);
        if (empty($perm[$action])) {
            abort(403, "Forbidden ({$action})");
        }
    }

    private function abortIfNoMenuAccess(int $userId, string $menuUri): void
    {
        $roleId = (int) DB::table('users')->where('id', $userId)->value('role_id');
        $has = DB::table('role_menu_mappings as rmm')
            ->join('menus as m', 'm.id', '=', 'rmm.menu_id')
            ->where('m.uri', $menuUri)
            ->where('rmm.role_id', $roleId)
            ->whereNull('rmm.deleted_at')
            ->exists();

        if (!$has) {
            abort(403, 'Forbidden (no menu access)');
        }
    }
}
