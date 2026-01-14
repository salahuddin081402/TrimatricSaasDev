<?php

namespace App\Http\Controllers\SuperAdmin\GlobalSetup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SuperAdmin\UserManagement\User; // Jurisdiction needs this
use App\Support\Jurisdiction\Jurisdiction;     // Jurisdiction service

class ClusterController extends Controller
{
    private ?int $userId = null;
    private ?int $companyId = null;

    /** Must match menus.uri for Clusters index */
    private string $menuUri = 'superadmin.globalsetup.clusters.index';

    public function __construct()
    {
        /*
        $forcedUserId = config('header.dev_force_user_id'); // null or an id
        $this->userId = Auth::id() ?? $forcedUserId;
        */
        $forcedUserId = config('header.dev_force_user_id');

        // If dev forced user id is set, ALWAYS use it for this module.
        if (is_numeric( $forcedUserId)) {
            $this->userId =   $forcedUserId;
        }

        $this->userId =  Auth::id();

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

    /** List + search + sort + offset/limit pagination, with Filterbar + jurisdiction */
    public function index(Request $request)
    {
        $this->abortIfNoMenuAccess($this->userId, $this->menuUri);

        // Resolve user + jurisdiction context
        $user = $this->userId ? User::find($this->userId) : null;
        abort_if(!$user, 403, 'Forbidden (no user)');
        $ctx  = app(Jurisdiction::class)->forUser($user);

        $page   = max(1, (int) $request->query('page', 1));
        $limit  = max(1, min(100, (int) $request->query('limit', 10)));
        $search = trim((string) $request->query('search', ''));
        $offset = ($page - 1) * $limit;

        $sort = (string) $request->query('sort', 'id');
        $dir  = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['id', 'short_code', 'cluster_name', 'status', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'id';

        // Optional filters from Filterbar (respect '__all__' meaning "no extra filter")
        $divisionId = $request->query('division_id');
        $districtId = $request->query('district_id');
        $clusterId  = $request->query('cluster_id');

        $base = DB::table('cluster_masters as cm')
            ->join('districts as d', 'd.id', '=', 'cm.district_id')
            ->join('divisions as dv', 'dv.id', '=', 'd.division_id')
            ->select(
                'cm.id','cm.short_code','cm.cluster_name','cm.status','cm.created_at',
                'd.id as district_id','d.name as district_name','d.short_code as district_code',
                'dv.id as division_id','dv.name as division_name','dv.short_code as division_code',
                'cm.cluster_supervisor_id'
            )
            // Always tenant-scope
            ->where('cm.company_id', $this->companyId ?? 0);

        // ---- Jurisdiction wall (hard constraint) ----
        switch ($ctx->level) {
            case $ctx::LEVEL_GLOBAL:
                // all clusters within this company
                break;
            case ($ctx::LEVEL_DIVISION):
                $base->where('dv.id', $ctx->division_id);
                break;
            case ($ctx::LEVEL_DISTRICT):
                $base->where('d.id', $ctx->district_id);
                break;
            case ($ctx::LEVEL_CLUSTER):
                $base->where('cm.id', $ctx->cluster_id);
                break;
            default:
                // none
                $base->whereRaw('1=0');
        }

        // ---- Search ----
        if ($search !== '') {
            $like = '%'.$search.'%';
            $base->where(function($q) use ($like) {
                $q->where('cm.cluster_name','like',$like)
                  ->orWhere('cm.short_code','like',$like)
                  ->orWhere('d.name','like',$like)
                  ->orWhere('dv.name','like',$like);
            });
        }

        // ---- Filterbar narrowing (within jurisdiction only) ----
        if (!empty($divisionId) && $divisionId !== '__all__') {
            $base->where('dv.id', (int)$divisionId);
        }
        if (!empty($districtId) && $districtId !== '__all__') {
            $base->where('d.id', (int)$districtId);
        }
        if (!empty($clusterId) && $clusterId !== '__all__') {
            $base->where('cm.id', (int)$clusterId);
        }

        $total = (clone $base)->count();

        $rows = (clone $base)
            ->orderBy($sort === 'created_at' ? 'cm.created_at' : "cm.{$sort}", $dir)
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function($r){
                // Supervisor: Full Name + (phone, email)
                $display = '—';
                if (!empty($r->cluster_supervisor_id)) {
                    $reg = DB::table('registration_master')
                        ->where('user_id', $r->cluster_supervisor_id)
                        ->orderByDesc('id')
                        ->select('full_name','phone','email')
                        ->first();

                    if ($reg) {
                        $name = trim((string)($reg->full_name ?? ''));
                        $parts = [];
                        if (!empty($reg->phone)) $parts[] = $reg->phone;
                        if (!empty($reg->email)) $parts[] = $reg->email;

                        if ($name !== '' && !empty($parts))      $display = $name.' ('.implode(', ', $parts).')';
                        elseif ($name !== '' && empty($parts))    $display = $name;
                        elseif ($name === ''  && !empty($parts))  $display = '('.implode(', ', $parts).')';
                        else                                       $display = '—';
                    }
                }
                $r->supervisor_name = $display;

                // Upazila list (display) — tenant safe
                $upazilas = DB::table('cluster_upazila_mappings as m')
                    ->join('upazilas as u','u.id','=','m.upazila_id')
                    ->where('m.cluster_id', $r->id)
                    ->where('m.company_id', request()->user()->company_id ?? (int)DB::table('cluster_masters')->where('id',$r->id)->value('company_id') ?? 0)
                    ->orderBy('u.name')
                    ->pluck('u.name')
                    ->toArray();
                $r->upazila_names = implode(', ', $upazilas);

                return $r;
            });

        $totalPages = max(1, (int) ceil($total / $limit));
        $winStart   = max(1, $page - 3);
        $winEnd     = min($totalPages, $page + 3);

        $can = $this->actionPermissions($this->userId, $this->menuUri);

        // === AJAX? Return ONLY the @section('content') fragment (no layout header/footer) ===
        if ($request->ajax()) {
            $view = view(
                'backend.modules.global-setup.clusters.index',
                compact(
                    'rows','page','limit','search','total','totalPages','winStart','winEnd','can','sort','dir',
                    'divisionId','districtId','clusterId'
                ) + ['onlyTable' => true]
            );
            $sections = $view->renderSections();
            return response($sections['content'] ?? '');
        }

        // Full page (normal)
        return view('backend.modules.global-setup.clusters.index', compact(
            'rows','page','limit','search','total','totalPages','winStart','winEnd','can','sort','dir',
            'divisionId','districtId','clusterId'
        ))->with('title', 'Clusters');
    }

    public function create(Request $request)
    {
        $this->abortIfForbidden('create');

        // Context passed from index (optional)
        $divisionId = $request->query('division_id');
        $districtId = $request->query('district_id');

        $divisions = DB::table('divisions')->orderBy('short_code')->get(['id','name','short_code']);

        $districts = [];
        if (!empty($divisionId)) {
            $districts = DB::table('districts')
                ->where('division_id', (int)$divisionId)
                ->orderBy('short_code')
                ->get(['id','name','short_code']);
        }

        // Upazilas in chosen district that are not yet mapped (tenant-safe)
        $upazilas = [];
        if (!empty($districtId)) {
            $used = DB::table('cluster_upazila_mappings')
                ->where('company_id', $this->companyId ?? 0)
                ->where('status',1)
                ->pluck('upazila_id')->toArray();

            $upazilas = DB::table('upazilas')
                ->where('district_id', (int)$districtId)
                ->when(!empty($used), fn($q)=>$q->whereNotIn('id',$used))
                ->orderBy('name')
                ->get(['id','name','short_code']);
        }

        return view('backend.modules.global-setup.clusters.create', [
            'title'      => 'Add Cluster',
            'divisions'  => $divisions,
            'districts'  => $districts,
            'upazilas'   => $upazilas,
            'divisionId' => $divisionId,
            'districtId' => $districtId,
        ]);
    }

    public function store(Request $request)
    {
        $this->abortIfForbidden('create');

        $validated = $request->validate([
            'division_id'  => ['required','integer','exists:divisions,id'],
            'district_id'  => ['required','integer','exists:districts,id'],
            'cluster_name' => ['required','string','max:150'],
            'status'       => ['required','in:0,1'],
            'upazila_ids'  => ['array'],
            'upazila_ids.*'=> ['integer','exists:upazilas,id'],
        ]);

        // Insert cluster
        $districtCode = DB::table('districts')->where('id',$validated['district_id'])->value('short_code') ?? '';
        $nextNo = (int) DB::table('cluster_masters')
                    ->where('company_id', $this->companyId ?? 0)
                    ->where('district_id',$validated['district_id'])
                    ->max('cluster_no');
        $nextNo = $nextNo + 1;
        $short  = $districtCode . str_pad((string)$nextNo, 2, '0', STR_PAD_LEFT);

        $clusterId = DB::table('cluster_masters')->insertGetId([
            'company_id'            => $this->companyId ?: 0,
            'district_id'           => (int)$validated['district_id'],
            'cluster_no'            => $nextNo,
            'short_code'            => $short,
            'cluster_name'          => $validated['cluster_name'],
            'cluster_supervisor_id' => null,
            'status'                => (int)$validated['status'],
            'created_by'            => $this->userId ?: 0,
            'updated_by'            => $this->userId ?: 0,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        // Map upazilas (tenant-safe; exclude already mapped within this company)
        $upazilaIds = collect($validated['upazila_ids'] ?? [])->unique()->values()->all();
        if (!empty($upazilaIds)) {
            $already = DB::table('cluster_upazila_mappings')
                ->where('company_id', $this->companyId ?? 0)
                ->whereIn('upazila_id', $upazilaIds)
                ->pluck('upazila_id')->toArray();

            $toInsert = array_diff($upazilaIds, $already);

            $batch = [];
            foreach ($toInsert as $uid) {
                $batch[] = [
                    'company_id' => $this->companyId ?: 0,
                    'cluster_id' => $clusterId,
                    'upazila_id' => (int)$uid,
                    'status'     => 1,
                    'created_by' => $this->userId ?: 0,
                    'updated_by' => $this->userId ?: 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($batch) DB::table('cluster_upazila_mappings')->insert($batch);
        }

        // Redirect to jurisdiction-wide list (no forced division/district)
        return redirect()->route('superadmin.globalsetup.clusters.index')
            ->with('status','Cluster created successfully.');
    }

    public function show($id)
    {
        $this->abortIfForbidden('view');

        $row = DB::table('cluster_masters as cm')
            ->join('districts as d','d.id','=','cm.district_id')
            ->join('divisions as dv','dv.id','=','d.division_id')
            ->where('cm.id',$id)
            ->where('cm.company_id', $this->companyId ?? 0)
            ->select(
                'cm.*',
                'd.name as district_name','d.short_code as district_code',
                'dv.name as division_name','dv.short_code as division_code'
            )->first();

        abort_if(!$row, 404);

        $supervisorName = '—';
        if (!empty($row->cluster_supervisor_id)) {
            $reg = DB::table('registration_master')
                ->where('user_id', $row->cluster_supervisor_id)
                ->orderByDesc('id')
                ->select('full_name','phone','email')
                ->first();

            if ($reg) {
                $name = trim((string)($reg->full_name ?? ''));
                $parts = [];
                if (!empty($reg->phone)) $parts[] = $reg->phone;
                if (!empty($reg->email)) $parts[] = $reg->email;

                if ($name !== '' && !empty($parts))      $supervisorName = $name.' ('.implode(', ', $parts).')';
                elseif ($name !== '' && empty($parts))    $supervisorName = $name;
                elseif ($name === ''  && !empty($parts))  $supervisorName = '('.implode(', ', $parts).')';
                else                                       $supervisorName = '—';
            }
        }

        $upazilas = DB::table('cluster_upazila_mappings as m')
            ->join('upazilas as u','u.id','=','m.upazila_id')
            ->where('m.cluster_id', $id)
            ->where('m.company_id', $this->companyId ?? 0)
            ->orderBy('u.name')
            ->get(['u.short_code','u.name']);

        return view('backend.modules.global-setup.clusters.show', [
            'title'          => 'View Cluster',
            'row'            => $row,
            'supervisorName' => $supervisorName,
            'upazilas'       => $upazilas,
        ]);
    }

    public function edit($id)
    {
        $this->abortIfForbidden('edit');

        $row = DB::table('cluster_masters')
            ->where('id',$id)
            ->where('company_id', $this->companyId ?? 0)
            ->first();
        abort_if(!$row, 404);

        $divisionId = DB::table('districts')->where('id',$row->district_id)->value('division_id');

        $divisions = DB::table('divisions')->orderBy('short_code')->get(['id','name','short_code']);
        $districts = DB::table('districts')->where('division_id',$divisionId)->orderBy('short_code')->get(['id','name','short_code']);

        $supervisorName = '—';
        if (!empty($row->cluster_supervisor_id)) {
            $reg = DB::table('registration_master')
                ->where('user_id', $row->cluster_supervisor_id)
                ->orderByDesc('id')
                ->select('full_name','phone','email')
                ->first();

            if ($reg) {
                $name = trim((string)($reg->full_name ?? ''));
                $parts = [];
                if (!empty($reg->phone)) $parts[] = $reg->phone;
                if (!empty($reg->email)) $parts[] = $reg->email;

                if ($name !== '' && !empty($parts))      $supervisorName = $name.' ('.implode(', ', $parts).')';
                elseif ($name !== '' && empty($parts))    $supervisorName = $name;
                elseif ($name === ''  && !empty($parts))  $supervisorName = '('.implode(', ', $parts).')';
                else                                       $supervisorName = '—';
            }
        }

        // Upazilas for the district; mark which are already mapped to this cluster
        $mapped = DB::table('cluster_upazila_mappings')
            ->where('company_id', $this->companyId ?? 0)
            ->where('cluster_id', $id)
            ->pluck('upazila_id')->toArray();

        // Upazilas assigned to other clusters (disable them)
        $taken = DB::table('cluster_upazila_mappings')
            ->where('company_id', $this->companyId ?? 0)
            ->where('cluster_id','<>',$id)
            ->pluck('upazila_id')->toArray();

        $upazilas = DB::table('upazilas')
            ->where('district_id', $row->district_id)
            ->orderBy('name')
            ->get(['id','name','short_code'])
            ->map(function($u) use ($mapped,$taken){
                $u->checked  = in_array($u->id, $mapped, true);
                $u->disabled = in_array($u->id, $taken, true) && !$u->checked;
                return $u;
            });

        return view('backend.modules.global-setup.clusters.edit', [
            'title'          => 'Edit Cluster',
            'row'            => $row,
            'divisionId'     => $divisionId,
            'divisions'      => $divisions,
            'districts'      => $districts,
            'supervisorName' => $supervisorName,
            'upazilas'       => $upazilas,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->abortIfForbidden('edit');

        $row = DB::table('cluster_masters')
            ->where('id',$id)
            ->where('company_id', $this->companyId ?? 0)
            ->first();
        abort_if(!$row, 404);

        $validated = $request->validate([
            'division_id'  => ['required','integer','exists:divisions,id'],
            'district_id'  => ['required','integer','exists:districts,id'],
            'cluster_name' => ['required','string','max:150'],
            'status'       => ['required','in:0,1'],
            'upazila_ids'  => ['array'],
            'upazila_ids.*'=> ['integer','exists:upazilas,id'],
        ]);

        // If district changed, recompute short_code & cluster_no sequence (tenant-safe)
        $districtCode = DB::table('districts')->where('id',$validated['district_id'])->value('short_code') ?? '';
        if ((int)$validated['district_id'] !== (int)$row->district_id) {
            $nextNo = (int) DB::table('cluster_masters')
                        ->where('company_id', $this->companyId ?? 0)
                        ->where('district_id',$validated['district_id'])
                        ->max('cluster_no');
            $nextNo = $nextNo + 1;
            $short  = $districtCode . str_pad((string)$nextNo, 2, '0', STR_PAD_LEFT);

            DB::table('cluster_masters')->where('id',$id)->update([
                'district_id'  => (int)$validated['district_id'],
                'cluster_no'   => $nextNo,
                'short_code'   => $short,
                'cluster_name' => $validated['cluster_name'],
                'status'       => (int)$validated['status'],
                'updated_by'   => $this->userId ?: 0,
                'updated_at'   => now(),
            ]);
        } else {
            DB::table('cluster_masters')->where('id',$id)->update([
                'cluster_name' => $validated['cluster_name'],
                'status'       => (int)$validated['status'],
                'updated_by'   => $this->userId ?: 0,
                'updated_at'   => now(),
            ]);
        }

        // Sync upazilas (simple detach+attach; tenant-safe)
        $newIds  = collect($validated['upazila_ids'] ?? [])->unique()->values();
        $oldRows = DB::table('cluster_upazila_mappings')
            ->where('company_id', $this->companyId ?? 0)
            ->where('cluster_id',$id)
            ->get(['id','upazila_id']);
        $oldIds  = collect($oldRows)->pluck('upazila_id');

        $toAdd    = $newIds->diff($oldIds)->values();
        $toRemove = $oldIds->diff($newIds)->values();

        if ($toRemove->isNotEmpty()) {
            DB::table('cluster_upazila_mappings')
                ->where('company_id', $this->companyId ?? 0)
                ->where('cluster_id',$id)
                ->whereIn('upazila_id',$toRemove)
                ->delete();
        }

        if ($toAdd->isNotEmpty()) {
            // Block ones already taken by other clusters in this tenant
            $taken = DB::table('cluster_upazila_mappings')
                ->where('company_id', $this->companyId ?? 0)
                ->whereIn('upazila_id',$toAdd)->pluck('upazila_id')->toArray();
            $finalAdd = $toAdd->diff($taken)->values();

            $batch = [];
            foreach ($finalAdd as $uid) {
                $batch[] = [
                    'company_id' => $this->companyId ?: 0,
                    'cluster_id' => $id,
                    'upazila_id' => (int)$uid,
                    'status'     => 1,
                    'created_by' => $this->userId ?: 0,
                    'updated_by' => $this->userId ?: 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($batch) DB::table('cluster_upazila_mappings')->insert($batch);
        }

        // Redirect to jurisdiction-wide grid
        return redirect()->route('superadmin.globalsetup.clusters.index')
            ->with('status','Cluster updated successfully.');
    }

    /** AJAX hard delete with cascading children first */
    public function destroy($id)
    {
        $this->abortIfForbidden('delete');

        $exists = DB::table('cluster_masters')
            ->where('id',$id)
            ->where('company_id', $this->companyId ?? 0)
            ->exists();
        abort_if(!$exists, 404);

        DB::transaction(function () use ($id) {
            // delete children first (hard delete; tenant-safe)
            DB::table('cluster_upazila_mappings')
                ->where('company_id', $this->companyId ?? 0)
                ->where('cluster_id', $id)->delete();

            DB::table('cluster_members')
                ->where('company_id', $this->companyId ?? 0)
                ->where('cluster_id', $id)->delete();

            // then parent
            DB::table('cluster_masters')
                ->where('company_id', $this->companyId ?? 0)
                ->where('id', $id)->delete();
        });

        $this->logActivity('delete', 'cluster_masters', (int)$id, []);

        return response()->json(['ok'=>true,'id'=>(int)$id]);
    }

    /* ================= RBAC helpers (same as Company) ================= */

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
}
