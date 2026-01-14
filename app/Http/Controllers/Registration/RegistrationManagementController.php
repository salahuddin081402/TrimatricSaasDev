<?php
/**
 * TMX-RMGM | app/Http/Controllers/Registration/RegistrationManagementController.php
 * v10.0 final | 2025-11-06
 *
 * Delta vs v9.9:
 * - deassignClusterAdmin(): If supervisor loses last cluster_admin seat in this company and current role is 7,
 *   demote to role_id=8 (Cluster Member). Otherwise leave role unchanged. No call to maybeRevokeRole() here.
 */

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationManagementController extends Controller
{
    /* ================= Helpers ================= */

    private function currentUserId(): ?int
    {
        $forced = config('header.dev_force_user_id');

        // If dev forced user id is set, ALWAYS use it for this module.
        if (is_numeric($forced)) {
            return (int) $forced;
        }

        return Auth::id();
    }

    private function resolveCompany($routeCompany): ?object
    {
        if ($routeCompany instanceof \App\Models\SuperAdmin\GlobalSetup\Company) {
            return DB::table('companies')->where('id',$routeCompany->id)->where('status',1)->whereNull('deleted_at')->first();
        }
        if (is_numeric($routeCompany)) {
            return DB::table('companies')->where('id',$routeCompany)->where('status',1)->whereNull('deleted_at')->first();
        }
        if (is_string($routeCompany)) {
            $c = DB::table('companies')->where('slug',$routeCompany)->where('status',1)->whereNull('deleted_at')->first();
            if ($c) return $c;
        }
        $uid = $this->currentUserId();
        if ($uid) {
            $user = DB::table('users')->where('id',$uid)->first();
            if ($user && $user->company_id) {
                return DB::table('companies')->where('id',$user->company_id)->where('status',1)->whereNull('deleted_at')->first();
            }
            if ($user && $user->role_id) {
                return DB::table('companies')->where('status',1)->whereNull('deleted_at')->orderBy('id')->first();
            }
        }
        return null;
    }

    private function logActivity(string $action, ?int $uid, int $companyId, array $details = []): void
    {
        try {
            DB::table('activity_logs')->insert([
                'company_id'=>$companyId,'user_id'=>$uid,'action'=>$action,
                'table_name'=>'registration_master','row_id'=>$details['reg_id'] ?? null,
                'details'=>json_encode($details),'ip_address'=>request()->ip(),
                'time_local'=>now(),'time_dhaka'=>now(),'created_by'=>$uid,'updated_by'=>$uid,
                'created_at'=>now(),'updated_at'=>now(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('RMGM.logActivity', ['err'=>$e->getMessage()]);
        }
    }

    private function qxLog(string $where, QueryException $e): void
    {
        try {
            \Log::error($where.' QueryException', [
                'sql'=>method_exists($e,'getSql')?$e->getSql():null,
                'bindings'=>method_exists($e,'getBindings')?$e->getBindings():null,
                'message'=>$e->getMessage(),
            ]);
        } catch (\Throwable $ignore) {}
    }

    /** TMX-REV: unified payload for "already assigned" 422s */
    private function roleTakenPayload(string $code, string $roleLabel, ?int $adminUserId, ?string $adminName, array $extra = []): array
    {
        return array_merge([
            'ok'            => false,
            'code'          => $code,
            'msg'           => "{$roleLabel} already assigned",
            'role_name'     => $roleLabel,
            'admin_user_id' => $adminUserId,
            'admin_name'    => $adminName,
        ], $extra);
    }

    /** Column map for grid and distincts */
    private function gridColMap(): array
    {
        return [
            'id' => 'rm.id',
            'user' => 'u.name',
            'phone' => 'rm.phone',
            'reg_type' => 'rm.registration_type',
            'division' => 'dv.name',
            'district' => 'ds.name',
            'upazila' => 'uz.name',
            'approval_status' => 'rm.approval_status',
            'role' => 'r.name',
        ];
    }

    /** Demote to Guest if no active footprint for current role */
    private function maybeRevokeRole(int $companyId, int $userId): void
    {
        $u = DB::table('users')->where('id',$userId)->select('role_id')->first();
        if (!$u) return;
        $rid = (int)$u->role_id;

        if ($rid === 5) {
            $has = DB::table('company_division_admins')->where([
                ['company_id','=',$companyId],['user_id','=',$userId],['status','=',1]
            ])->exists();
            if (!$has) DB::table('users')->where('id',$userId)->update(['role_id'=>10,'updated_at'=>now()]);
        } elseif ($rid === 6) {
            $has = DB::table('company_district_admins')->where([
                ['company_id','=',$companyId],['user_id','=',$userId],['status','=',1]
            ])->exists();
            if (!$has) DB::table('users')->where('id',$userId)->update(['role_id'=>10,'updated_at'=>now()]);
        } elseif ($rid === 7) {
            $has = DB::table('cluster_masters')->where([
                ['company_id','=',$companyId],['cluster_supervisor_id','=',$userId]
            ])->exists();
            if (!$has) DB::table('users')->where('id',$userId)->update(['role_id'=>10,'updated_at'=>now()]);
        } elseif ($rid === 8) {
            $has = DB::table('cluster_members')->where([
                ['company_id','=',$companyId],['user_id','=',$userId],['status','=',1]
            ])->exists();
            if (!$has) DB::table('users')->where('id',$userId)->update(['role_id'=>10,'updated_at'=>now()]);
        }
    }

    /* ================= Page ================= */

    public function index(Request $req, string $company)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        return view('registration.Registration_Management.registration_mgmt', [
            'companyParam'=>$company, 'company'=>$co,
        ]);
    }

    /* ================= Data API ================= */
    public function data(Request $req, string $company)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);

        $size   = max(10, min((int)$req->get('size', 25), 100));
        $page   = max(1, (int)$req->get('page', 1));
        $search = trim((string)$req->get('search', ''));
        $sortBy = (string)$req->get('sort_by', 'id');
        $sortDir= strtolower((string)$req->get('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $spCol  = (string)$req->get('sp_col', '');
        $spVal  = (string)$req->get('sp_val', '');

        $colMap = $this->gridColMap();
        if (!isset($colMap[$sortBy])) $sortBy = 'id';
        $sortCol = $colMap[$sortBy];

        try {
            $q = DB::table('registration_master as rm')
                ->leftJoin('users as u','u.id','=','rm.user_id')
                ->leftJoin('roles as r','r.id','=','u.role_id')
                ->leftJoin('role_types as rt','rt.id','=','r.role_type_id')
                ->leftJoin('divisions as dv','dv.id','=','rm.division_id')
                ->leftJoin('districts as ds','ds.id','=','rm.district_id')
                ->leftJoin('upazilas as uz','uz.id','=','rm.upazila_id')
                ->select([
                    'rm.id','rm.company_id','rm.user_id','rm.registration_type','rm.phone',
                    'rm.status','rm.approval_status',
                    'u.name as user_name','u.role_id',
                    DB::raw("CONCAT(COALESCE(dv.short_code,''),' ',COALESCE(dv.name,'')) as division_name"),
                    DB::raw("CONCAT(COALESCE(ds.short_code,''),' ',COALESCE(ds.name,'')) as district_name"),
                    DB::raw("CONCAT(COALESCE(uz.short_code,''),' ',COALESCE(uz.name,'')) as upazila_name"),
                    'r.name as role_name',
                    'rt.name as role_type_name',
                    DB::raw("CASE WHEN rm.registration_type='company_officer' AND rm.approval_status='approved' AND u.role_id BETWEEN 2 AND 8 THEN 1 ELSE 0 END as can_promote"),
                    DB::raw("CASE WHEN rm.approval_status='approved' AND rm.user_id IS NOT NULL THEN 1 ELSE 0 END as can_assign"),
                ])
                ->where('rm.company_id',$co->id)
                ->whereNull('rm.deleted_at');

            // NEW: optional exact match by user_id, numeric only. No effect if empty or non-numeric.
            $filterUid = trim((string)$req->get('user_id', ''));
            if ($filterUid !== '' && ctype_digit($filterUid)) {
                $q->where('u.id', (int)$filterUid);
            }

            if ($search !== '') {
                $like = '%'.$search.'%';
                $q->where(function($w) use ($like, $search){
                    $w->where('u.name','like',$like)
                      ->orWhere('rm.phone','like',$like)
                      ->orWhere('rm.registration_type','like',$like)
                      ->orWhere('rm.approval_status','like',$like)
                      ->orWhere('r.name','like',$like)
                      ->orWhere('dv.name','like',$like)->orWhere('dv.short_code','like',$like)
                      ->orWhere('ds.name','like',$like)->orWhere('ds.short_code','like',$like)
                      ->orWhere('uz.name','like',$like)->orWhere('uz.short_code','like',$like)
                      ->orWhere('rm.id','like',$like)->orWhere('u.id','like',$like);
                    if (ctype_digit($search)) {
                        $w->orWhere('rm.id',(int)$search)->orWhere('u.id',(int)$search);
                    }
                });
            }

            if ($spCol && $spVal !== '') {
                if ($spCol === 'reg_type') {
                    $q->where('rm.registration_type', $spVal);
                } elseif (isset($colMap[$spCol])) {
                    $q->where($colMap[$spCol], $spVal);
                }
            }

            $total = (clone $q)->count();
            $rows  = $q->orderBy($sortCol, $sortDir)->forPage($page, $size)->get();

            $counts = [
                'total'    => DB::table('registration_master')->where('company_id',$co->id)->whereNull('deleted_at')->count(),
                'approved' => DB::table('registration_master')->where('company_id',$co->id)->where('approval_status','approved')->whereNull('deleted_at')->count(),
                'pending'  => DB::table('registration_master')->where('company_id',$co->id)->where('approval_status','pending')->whereNull('deleted_at')->count(),
                'declined' => DB::table('registration_master')->where('company_id',$co->id)->where('approval_status','declined')->whereNull('deleted_at')->count(),
                'guests'   => DB::table('users')->where('company_id',$co->id)->where('role_id',10)->count(),
                'role5_div_admins' => DB::table('users')->where('company_id',$co->id)->where('role_id',5)->count(),
                'role6_dist_admins'=> DB::table('users')->where('company_id',$co->id)->where('role_id',6)->count(),
                'role7_supervisors'=> DB::table('users')->where('company_id',$co->id)->where('role_id',7)->count(),
                'role8_members'    => DB::table('users')->where('company_id',$co->id)->where('role_id',8)->count(),
            ];

            return response()->json(['ok'=>true,'rows'=>$rows,'counts'=>$counts,'page'=>$page,'size'=>$size,'total'=>$total]);
        } catch (\Illuminate\Database\QueryException $e) {
            $this->qxLog('RMGM.data', $e);
            return response()->json(['ok'=>false,'msg'=>'DB error'], 500);
        }
    }

    /** Distinct values for “Special Value”, by column key */
    public function distinctValues(Request $req, string $company)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $col = (string)$req->query('col','');

        if ($col === 'reg_type') {
            return response()->json(['ok'=>true,'values'=>['client','company_officer','professional']]);
        }
        if ($col === 'approval_status') {
            return response()->json(['ok'=>true,'values'=>['pending','approved','declined']]);
        }
        if ($col === 'role') {
            $vals = DB::table('roles')->whereBetween('id',[1,11])->orderBy('id')->pluck('name');
            return response()->json(['ok'=>true,'values'=>$vals]);
        }

        $map = $this->gridColMap();
        if (!$col || !isset($map[$col])) return response()->json(['ok'=>false,'msg'=>'Bad column'],422);

        $vals = DB::table('registration_master as rm')
            ->leftJoin('users as u','u.id','=','rm.user_id')
            ->leftJoin('roles as r','r.id','=','u.role_id')
            ->leftJoin('divisions as dv','dv.id','=','rm.division_id')
            ->leftJoin('districts as ds','ds.id','=','rm.district_id')
            ->leftJoin('upazilas as uz','uz.id','=','rm.upazila_id')
            ->where('rm.company_id',$co->id)->whereNull('rm.deleted_at')
            ->distinct()->pluck($map[$col])->filter()->values();

        return response()->json(['ok'=>true,'values'=>$vals]);
    }

    /** Radios for modal. Optional ?reg_id=123 for lock hinting */
    public function roles(Request $req, string $company)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $regId = (int)$req->query('reg_id', 0);

        if ($regId > 0) {
            $rm = DB::table('registration_master as rm')
                ->leftJoin('users as u','u.id','=','rm.user_id')
                ->where('rm.company_id',$co->id)->where('rm.id',$regId)->whereNull('rm.deleted_at')
                ->first(['rm.approval_status','rm.registration_type','u.role_id']);

            if ($rm && $rm->approval_status === 'approved' && $rm->role_id >= 1 && $rm->role_id <= 8) {
                $current = (int)$rm->role_id;

                // Keep hard lock only for top office roles (1..4) so UI doesn't open a no-op modal.
                if ($current >= 1 && $current <= 4) {
                    $only = DB::table('roles')->where('id',$current)->get(['id','name']);
                    return response()->json([
                        'ok'=>true,'roles'=>$only,'locked'=>true,'current_role_id'=>$current
                    ]);
                }

                // For field roles (5..8): return full list and unlocked so modal opens.
                $roles = DB::table('roles')->whereBetween('id',[1,8])->orderBy('id')->get(['id','name']);
                return response()->json([
                    'ok'=>true,'roles'=>$roles,'locked'=>false,'current_role_id'=>$current
                ]);
            }
        }

        $roles = DB::table('roles')->whereBetween('id',[1,8])->orderBy('id')->get(['id','name']);
        return response()->json(['ok'=>true,'roles'=>$roles,'locked'=>false]);
    }

    /* ============ Geo ============ */

    public function geoDivisions(Request $req, string $company)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $excludeAssignedForUser = (int)$req->get('exclude_user_id', 0);
        $q = DB::table('divisions')->select(['id','short_code','name'])->orderBy('name');
        if ($excludeAssignedForUser) {
            $q->whereNotIn('id', function($s) use($co,$excludeAssignedForUser){
                $s->from('company_division_admins')->select('division_id')
                  ->where('company_id',$co->id)->where('user_id',$excludeAssignedForUser)->where('status',1);
            });
        }
        return response()->json(['ok'=>true,'items'=>$q->get()]);
    }

    public function geoDistricts(Request $req, string $company)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $divisionId = (int)$req->get('division_id', 0);
        $excludeAssignedForUser = (int)$req->get('exclude_user_id', 0);
        $q = DB::table('districts')->select(['id','short_code','name'])
            ->when($divisionId>0,function($w) use($divisionId){ $w->where('division_id',$divisionId); })
            ->orderBy('name');
        if ($excludeAssignedForUser) {
            $q->whereNotIn('id', function($s) use($co,$excludeAssignedForUser){
                $s->from('company_district_admins')->select('district_id')
                  ->where('company_id',$co->id)->where('user_id',$excludeAssignedForUser)->where('status',1);
            });
        }
        return response()->json(['ok'=>true,'items'=>$q->get()]);
    }

    public function geoClusters(Request $req, string $company)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $districtId = (int)$req->get('district_id', 0);
        $excludeMemberUser = (int)$req->get('exclude_member_user_id', 0);
        $excludeSupervisorUser = (int)$req->get('exclude_supervisor_user_id', 0);

        $q = DB::table('cluster_masters as cm')->select(['cm.id','cm.short_code','cm.cluster_name'])
            ->where('cm.company_id',$co->id)->where('cm.status',1)
            ->when($districtId>0,function($w) use($districtId){ $w->where('cm.district_id',$districtId); })
            ->orderBy('cm.cluster_name');

        if ($excludeMemberUser) {
            $q->whereNotIn('cm.id', function($s) use($co,$excludeMemberUser){
                $s->from('cluster_members')->select('cluster_id')
                  ->where('company_id',$co->id)->where('user_id',$excludeMemberUser)->where('status',1);
            });
        }
        if ($excludeSupervisorUser) {
            $q->whereNotIn('cm.id', function($s) use($co,$excludeSupervisorUser){
                $s->from('cluster_masters')->select('id')
                  ->where('company_id',$co->id)->where('cluster_supervisor_id',$excludeSupervisorUser)->where('status',1);
            });
        }

        return response()->json(['ok'=>true,'items'=>$q->get()]);
    }

    /* ============ Modal Info + Lists ============ */

    public function modalInfo(Request $req, string $company, int $regId)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);

        $rm = DB::table('registration_master as rm')
            ->leftJoin('users as u','u.id','=','rm.user_id')
            ->leftJoin('roles as r','r.id','=','u.role_id')
            ->select('rm.id as reg_id','rm.company_id','rm.user_id','rm.approval_status','u.name as user_name','u.role_id','r.name as role_name')
            ->where('rm.company_id',$co->id)->where('rm.id',$regId)->whereNull('rm.deleted_at')->first();
        abort_unless($rm, 404);

        $roleId = (int)$req->get('role_id', (int)$rm->role_id);
        $rows = [];

        if ($roleId === 5)       { $rows = $this->assignedDivisions($req, $company, $rm->user_id, true); }
        elseif ($roleId === 6)   { $rows = $this->assignedDistricts($req, $company, $rm->user_id, true); }
        elseif ($roleId === 7)   { $rows = $this->assignedSupervisedClusters($req, $company, $rm->user_id, true); }
        elseif ($roleId === 8)   { $rows = $this->assignedClusters($req, $company, $rm->user_id, true); }

        return response()->json([
            'ok'=>true,
            'user_id'=>$rm->user_id,
            'approval_status'=>$rm->approval_status,
            'role_id'=>(int)$rm->role_id,
            'role_name'=>$rm->role_name,
            'rows'=>$rows,
        ]);
    }

    public function assignedDivisions(Request $req, string $company, int $user, bool $internal=false)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $rows = DB::table('company_division_admins as t')
            ->leftJoin('divisions as dv','dv.id','=','t.division_id')
            ->leftJoin('users as u','u.id','=','t.user_id')
            ->where('t.company_id',$co->id)->where('t.user_id',$user)->where('t.status',1)
            ->orderBy('dv.name')
            ->get(['u.id as user_id','u.name','dv.name as division','t.created_at as assigned_at','t.division_id']);
        if ($internal) return $rows;
        return response()->json(['ok'=>true,'rows'=>$rows]);
    }

    public function assignedDistricts(Request $req, string $company, int $user, bool $internal=false)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $rows = DB::table('company_district_admins as t')
            ->leftJoin('districts as ds','ds.id','=','t.district_id')
            ->leftJoin('divisions as dv','dv.id','=','ds.division_id')
            ->leftJoin('users as u','u.id','=','t.user_id')
            ->where('t.company_id',$co->id)->where('t.user_id',$user)->where('t.status',1)
            ->orderBy('dv.name')->orderBy('ds.name')
            ->get(['u.id as user_id','u.name','dv.name as division','ds.name as district','t.created_at as assigned_at','t.district_id']);
        if ($internal) return $rows;
        return response()->json(['ok'=>true,'rows'=>$rows]);
    }

    public function assignedClusters(Request $req, string $company, int $user, bool $internal=false)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $rows = DB::table('cluster_members as m')
            ->leftJoin('cluster_masters as cm','cm.id','=','m.cluster_id')
            ->leftJoin('districts as ds','ds.id','=','cm.district_id')
            ->leftJoin('divisions as dv','dv.id','=','ds.division_id')
            ->leftJoin('users as u','u.id','=','m.user_id')
            ->where('m.company_id',$co->id)->where('m.user_id',$user)->where('m.status',1)
            ->orderBy('dv.name')->orderBy('ds.name')->orderBy('cm.cluster_name')
            ->get(['u.id as user_id','u.name','dv.name as division','ds.name as district','cm.cluster_name','cm.short_code','cm.id as cluster_id']);
        if ($internal) return $rows;
        return response()->json(['ok'=>true,'rows'=>$rows]);
    }

    public function assignedSupervisedClusters(Request $req, string $company, int $user, bool $internal=false)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $rows = DB::table('cluster_masters as cm')
            ->leftJoin('districts as ds','ds.id','=','cm.district_id')
            ->leftJoin('divisions as dv','dv.id','=','ds.division_id')
            ->leftJoin('users as u','u.id','=','cm.cluster_supervisor_id')
            ->where('cm.company_id',$co->id)->where('cm.cluster_supervisor_id',$user)
            ->orderBy('dv.name')->orderBy('ds.name')->orderBy('cm.cluster_name')
            ->get(['u.id as user_id','u.name','dv.name as division','ds.name as district','cm.cluster_name','cm.short_code','cm.id as cluster_id']);
        if ($internal) return $rows;
        return response()->json(['ok'=>true,'rows'=>$rows]);
    }

    /* ============ De-assign (scoped) ============ */

    public function deassignCore(Request $req, string $company, int $regId)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $uid = $this->currentUserId();

        $rm = DB::table('registration_master')->where('id',$regId)->where('company_id',$co->id)->whereNull('deleted_at')->first();
        abort_unless($rm, 404);
        if (!$rm->user_id) return response()->json(['ok'=>false,'msg'=>'No linked user'],422);

        try{
            DB::transaction(function() use($rm,$uid){
                DB::table('registration_master')->where('id',$rm->id)->update([
                    'status'=>0,'approval_status'=>'pending','updated_by'=>$uid,'updated_at'=>now()
                ]);
                DB::table('users')->where('id',$rm->user_id)->update(['role_id'=>10,'updated_at'=>now()]);
            });
            $this->logActivity('deassign_core',$uid,$rm->company_id,['reg_id'=>$rm->id,'user_id'=>$rm->user_id]);
            return response()->json(['ok'=>true,'msg'=>'Role de-assigned. Registration set to pending.']);
        } catch (QueryException $e){ $this->qxLog('RMGM.deassignCore',$e); return response()->json(['ok'=>false,'msg'=>'DB error'],500); }
    }

    public function deassignDivision(Request $req, string $company)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $uid = $this->currentUserId();
        $userId = (int)$req->input('user_id');
        $divisionId = (int)$req->input('division_id');
        if($userId<1||$divisionId<1) return response()->json(['ok'=>false,'msg'=>'Bad params'],422);

        DB::transaction(function() use($co,$uid,$userId,$divisionId){
            DB::table('company_division_admins')->where([
                ['company_id','=',$co->id],['user_id','=',$userId],['division_id','=',$divisionId],['status','=',1]
            ])->update(['status'=>0,'updated_by'=>$uid,'updated_at'=>now()]);
            $this->maybeRevokeRole($co->id, $userId);
        });

        return response()->json(['ok'=>true,'msg'=>'Division de-assigned']);
    }

    public function deassignDistrict(Request $req, string $company)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $uid = $this->currentUserId();
        $userId = (int)$req->input('user_id');
        $districtId = (int)$req->input('district_id');
        if($userId<1||$districtId<1) return response()->json(['ok'=>false,'msg'=>'Bad params'],422);

        DB::transaction(function() use($co,$uid,$userId,$districtId){
            DB::table('company_district_admins')->where([
                ['company_id','=',$co->id],['user_id','=',$userId],['district_id','=',$districtId],['status','=',1]
            ])->update(['status'=>0,'updated_by'=>$uid,'updated_at'=>now()]);
            $this->maybeRevokeRole($co->id, $userId);
        });

        return response()->json(['ok'=>true,'msg'=>'District de-assigned']);
    }

    public function deassignClusterAdmin(Request $req, string $company)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $uid = $this->currentUserId();
        $clusterId = (int)$req->input('cluster_id');
        if($clusterId<1) return response()->json(['ok'=>false,'msg'=>'Bad params'],422);

        $row = DB::table('cluster_masters')->where('company_id',$co->id)->where('id',$clusterId)->first(['cluster_supervisor_id']);
        $supUserId = (int)($row->cluster_supervisor_id ?? 0);

        DB::transaction(function() use($co,$uid,$clusterId,$supUserId){
            // 1) Clear supervisor for this cluster
            DB::table('cluster_masters')->where('company_id',$co->id)->where('id',$clusterId)
                ->update(['cluster_supervisor_id'=>null,'updated_by'=>$uid,'updated_at'=>now()]);

            // 2) If there was a supervisor, check if they still supervise any other cluster in this company
            if ($supUserId > 0) {
                $stillAdminSomewhere = DB::table('cluster_masters')
                    ->where('company_id',$co->id)
                    ->where('cluster_supervisor_id',$supUserId)
                    ->exists();

                if (!$stillAdminSomewhere) {
                    // 3) Only if current role is 7 (Cluster Admin), demote to 8 (Cluster Member)
                    $currentRole = (int) DB::table('users')->where('id',$supUserId)->value('role_id');
                    if ($currentRole === 7) {
                        DB::table('users')->where('id',$supUserId)->update(['role_id'=>8,'updated_at'=>now()]);
                    }
                }
            }
        });

        return response()->json(['ok'=>true,'msg'=>'Cluster admin cleared']);
    }

    public function deassignClusterMember(Request $req, string $company)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $uid = $this->currentUserId();
        $userId = (int)$req->input('user_id');
        $clusterId = (int)$req->input('cluster_id');
        if($userId<1||$clusterId<1) return response()->json(['ok'=>false,'msg'=>'Bad params'],422);

        DB::transaction(function() use($co,$uid,$userId,$clusterId){
            DB::table('cluster_members')->where([
                ['company_id','=',$co->id],['user_id','=',$userId],['cluster_id','=',$clusterId],['status','=',1]
            ])->update(['status'=>0,'updated_by'=>$uid,'updated_at'=>now()]);
            $this->maybeRevokeRole($co->id, $userId);
        });

        return response()->json(['ok'=>true,'msg'=>'Cluster member de-assigned']);
    }

    /** Legacy shim */
    public function roleDeassign(Request $req, string $company, int $id)
    {
        $type = (string)$req->input('type','');
        if ($type==='core') return $this->deassignCore($req, $company, $id);
        if ($type==='division') return $this->deassignDivision($req, $company);
        if ($type==='district') return $this->deassignDistrict($req, $company);
        if ($type==='cluster_admin') return $this->deassignClusterAdmin($req, $company);
        if ($type==='cluster_member') return $this->deassignClusterMember($req, $company);
        return response()->json(['ok'=>false,'msg'=>'Unsupported de-assign type'],422);
    }

    /* ============ Approve / Decline / Terminate ============ */

    public function approve(Request $req, string $company, int $id)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $uid = $this->currentUserId();

        $rm = DB::table('registration_master')
            ->where('id',$id)->where('company_id',$co->id)->whereNull('deleted_at')->first();
        abort_unless($rm, 404);

        if (in_array($rm->registration_type,['client','professional'],true))
            return response()->json(['ok'=>false,'msg'=>'Not allowed for this registration type'],422);

        if ($rm->approval_status==='approved')
            return response()->json(['ok'=>true,'msg'=>'Already approved']);

        try {
            DB::transaction(function() use ($rm,$uid){
                DB::table('registration_master')->where('id',$rm->id)->update([
                    'status'=>1,'approval_status'=>'approved','updated_by'=>$uid,'updated_at'=>now(),
                ]);
            });
            $this->logActivity('approve',$uid,$rm->company_id,['reg_id'=>$rm->id]);
            return response()->json(['ok'=>true,'msg'=>'Approved']);
        } catch (QueryException $e) {
            $this->qxLog('RMGM.approve', $e);
            return response()->json(['ok'=>false,'msg'=>'DB error'],500);
        }
    }

    public function decline(Request $req, string $company, int $id)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $uid = $this->currentUserId();

        $rm = DB::table('registration_master')
            ->where('id',$id)->where('company_id',$co->id)->whereNull('deleted_at')->first();
        abort_unless($rm, 404);

        if (in_array($rm->registration_type,['client','professional'],true))
            return response()->json(['ok'=>false,'msg'=>'Not allowed for this registration type'],422);

        if ($rm->approval_status!=='pending')
            return response()->json(['ok'=>false,'msg'=>'Decline allowed only from pending'],422);

        try {
            DB::transaction(function() use ($rm,$uid){
                DB::table('registration_master')->where('id',$rm->id)->update([
                    'status'=>0,'approval_status'=>'declined','updated_by'=>$uid,'updated_at'=>now(),
                ]);
            });
            $this->logActivity('decline',$uid,$rm->company_id,['reg_id'=>$rm->id]);
            return response()->json(['ok'=>true,'msg'=>'Declined']);
        } catch (QueryException $e) {
            $this->qxLog('RMGM.decline', $e);
            return response()->json(['ok'=>false,'msg'=>'DB error'],500);
        }
    }

    public function terminate(Request $req, string $company, int $id)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $uid = $this->currentUserId();

        $rm = DB::table('registration_master')
            ->where('id',$id)->where('company_id',$co->id)->whereNull('deleted_at')->first();
        abort_unless($rm, 404);

        if (in_array($rm->registration_type,['client','professional'],true))
            return response()->json(['ok'=>false,'msg'=>'Not allowed for this registration type'],422);

        if ($rm->approval_status!=='approved')
            return response()->json(['ok'=>false,'msg'=>'Terminate allowed only from approved'],422);

        $user = DB::table('users')->where('id',$rm->user_id)->first();
        if (!$user) return response()->json(['ok'=>false,'msg'=>'User not found'],422);

        $role = DB::table('roles as r')->leftJoin('role_types as rt','rt.id','=','r.role_type_id')
            ->where('r.id',$user->role_id)->select('rt.name as role_type')->first();
        if ($role && in_array(strtolower((string)$role->role_type),['client','professional'],true))
            return response()->json(['ok'=>false,'msg'=>'Blocked for Client/Professional role types'],422);

        try {
            DB::transaction(function() use ($co,$uid,$rm,$user){
                DB::table('registration_master')->where('id',$rm->id)->update([
                    'status'=>0,'approval_status'=>'declined','updated_by'=>$uid,'updated_at'=>now(),
                ]);
                DB::table('users')->where('id',$user->id)->update(['role_id'=>10,'updated_at'=>now()]);
                DB::table('cluster_members')
                    ->where('company_id',$co->id)->where('user_id',$user->id)
                    ->update(['status'=>0,'updated_by'=>$uid,'updated_at'=>now()]);
                DB::table('company_division_admins')
                    ->where('company_id',$co->id)->where('user_id',$user->id)
                    ->update(['status'=>0,'updated_by'=>$uid,'updated_at'=>now()]);
                DB::table('company_district_admins')
                    ->where('company_id',$co->id)->where('user_id',$user->id)
                    ->update(['status'=>0,'updated_by'=>$uid,'updated_at'=>now()]);
                DB::table('cluster_masters')
                    ->where('company_id',$co->id)->where('cluster_supervisor_id',$user->id)
                    ->update(['cluster_supervisor_id'=>null,'updated_by'=>$uid,'updated_at'=>now()]);
            });

            $this->logActivity('terminate',$uid,$co->id,['reg_id'=>$rm->id,'user_id'=>$user->id]);
            return response()->json(['ok'=>true,'msg'=>'Terminated and demoted to Guest']);
        } catch (QueryException $e) {
            $this->qxLog('RMGM.terminate', $e);
            return response()->json(['ok'=>false,'msg'=>'DB error'],500);
        }
    }

    /**
     * Role Assign with lock and conflict handling.
     */
    public function roleAssign(Request $req, string $company, int $regId)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $uid = $this->currentUserId();

        $rm = DB::table('registration_master')->where('id',$regId)->where('company_id',$co->id)->whereNull('deleted_at')->first();
        abort_unless($rm, 404);
        if ($rm->approval_status !== 'approved' || $rm->registration_type !== 'company_officer')
            return response()->json(['ok'=>false,'msg'=>'Allowed only for approved company_officer'],422);
        if (!$rm->user_id) return response()->json(['ok'=>false,'msg'=>'No linked user'],422);

        $userId = (int)$rm->user_id;
        $user   = DB::table('users')->where('id',$userId)->first();
        if (!$user) return response()->json(['ok'=>false,'msg'=>'User not found'],422);
        $curRole = (int)($user->role_id ?? 0);

        $roleId     = (int)$req->input('role_id', 0);
        $divisionId = (int)$req->input('division_id', 0);
        $districtId = (int)$req->input('district_id', 0);
        $clusterId  = (int)$req->input('cluster_id', 0);

        if ($roleId === 0 && $curRole >=5 && $curRole <=8) $roleId = $curRole;
        if ($roleId < 1 || $roleId > 8) return response()->json(['ok'=>false,'msg'=>'Invalid role'],422);
        if ($curRole >= 1 && $curRole <= 8 && $roleId !== $curRole) {
            return response()->json([
                'ok'=>false,'code'=>'ROLE_LOCKED',
                'msg'=>'Re-assignment restricted to the existing role for approved users'
            ], 422);
        }

        try {
            DB::transaction(function() use ($co,$uid,$userId,$curRole,$roleId,$divisionId,$districtId,$clusterId,$rm){

                $setRole = function(int $rid) use ($userId){
                    DB::table('users')->where('id',$userId)->update(['role_id'=>$rid,'updated_at'=>now()]);
                };

                if ($roleId === 1) {
                    $occ = DB::table('users as u')
                        ->join('registration_master as r','r.user_id','=','u.id')
                        ->where('u.company_id',$co->id)
                        ->where('r.company_id',$co->id)
                        ->where('u.role_id',1)
                        ->where('r.approval_status','approved')
                        ->where('r.registration_type','company_officer')
                        ->where('u.id','<>',$userId)
                        ->lockForUpdate()
                        ->first(['u.id as occupant_user_id','u.name as occupant_name']);
                    if ($occ) {
                        $payload = $this->roleTakenPayload('SUPERADMIN_TAKEN', 'Super Admin', $occ->occupant_user_id ?? null, $occ->occupant_name ?? null);
                        throw new \Exception(json_encode($payload), 422);
                    }
                    $setRole(1);
                }
                elseif ($roleId === 2) {
                    $occ = DB::table('users as u')
                        ->join('registration_master as r','r.user_id','=','u.id')
                        ->where('u.company_id',$co->id)
                        ->where('r.company_id',$co->id)
                        ->where('u.role_id',2)
                        ->where('r.approval_status','approved')
                        ->where('r.registration_type','company_officer')
                        ->where('u.id','<>',$userId)
                        ->lockForUpdate()->first(['u.id as occupant_user_id','u.name as occupant_name']);
                    if ($occ) {
                        $payload = $this->roleTakenPayload('CEO_TAKEN', 'CEO', $occ->occupant_user_id ?? null, $occ->occupant_name ?? null);
                        throw new \Exception(json_encode($payload), 422);
                    }
                    $setRole(2);
                }
                elseif (in_array($roleId,[3,4],true)) {
                    $setRole($roleId);
                }
                elseif ($roleId === 5) {
                    if ($divisionId < 1) throw new \RuntimeException('Division required');
                    $already = DB::table('company_division_admins')
                        ->where('company_id',$co->id)->where('division_id',$divisionId)->where('user_id',$userId)->where('status',1)
                        ->exists();
                    if ($already) {
                        $setRole(5);
                    } else {
                        $occ = DB::table('company_division_admins')
                            ->where('company_id',$co->id)->where('division_id',$divisionId)->where('status',1)
                            ->lockForUpdate()->first();

                        if ($occ && (int)$occ->user_id !== $userId) {
                            $u = DB::table('users')->where('id',$occ->user_id)->first();
                            $payload = $this->roleTakenPayload('DIVISION_TAKEN', 'Division Admin', (int)$occ->user_id, $u->name ?? null, [
                                'division_id'=>$divisionId
                            ]);
                            throw new \Exception(json_encode($payload), 422);
                        }

                        $restored = DB::table('company_division_admins')
                            ->where('company_id',$co->id)->where('division_id',$divisionId)->where('user_id',$userId)->where('status',0)
                            ->update(['status'=>1,'updated_by'=>$uid,'updated_at'=>now()]);

                        if ($restored === 0) {
                            try {
                                DB::table('company_division_admins')->insert([
                                    'company_id'=>$co->id,'division_id'=>$divisionId,'user_id'=>$userId,'status'=>1,
                                    'created_by'=>$uid,'updated_by'=>$uid,'created_at'=>now(),'updated_at'=>now()
                                ]);
                            } catch (\Illuminate\Database\QueryException $qe) {
                                $msg = $qe->getMessage();
                                if (strpos($msg, 'Another active Division Admin') !== false) {
                                    $conf = DB::table('company_division_admins as t')
                                        ->leftJoin('users as u','u.id','=','t.user_id')
                                        ->where('t.company_id',$co->id)->where('t.division_id',$divisionId)->where('t.status',1)
                                        ->first(['t.user_id as admin_user_id','u.name as admin_name']);
                                    $payload = $this->roleTakenPayload('DIVISION_TAKEN','Division Admin',$conf->admin_user_id ?? null,$conf->admin_name ?? null,[
                                        'division_id'=>$divisionId
                                    ]);
                                    throw new \Exception(json_encode($payload), 422);
                                }
                                throw $qe;
                            }
                        }

                        $setRole(5);
                    }
                }
                elseif ($roleId === 6) {
                    if ($divisionId < 1 || $districtId < 1) throw new \RuntimeException('Division & District required');

                    $already = DB::table('company_district_admins')
                        ->where('company_id',$co->id)->where('district_id',$districtId)->where('user_id',$userId)->where('status',1)
                        ->exists();
                    if ($already) {
                        $setRole(6);
                    } else {
                        $occ = DB::table('company_district_admins')
                            ->where('company_id',$co->id)->where('district_id',$districtId)->where('status',1)
                            ->lockForUpdate()->first();
                        if ($occ && (int)$occ->user_id !== $userId) {
                            $u = DB::table('users')->where('id',$occ->user_id)->first();
                            $payload = $this->roleTakenPayload('DISTRICT_TAKEN','District Admin',(int)$occ->user_id,$u->name ?? null,[
                                'district_id'=>$districtId
                            ]);
                            throw new \Exception(json_encode($payload), 422);
                        }
                        $restored = DB::table('company_district_admins')
                            ->where('company_id',$co->id)->where('district_id',$districtId)->where('user_id',$userId)->where('status',0)
                            ->update(['status'=>1,'updated_by'=>$uid,'updated_at'=>now()]);
                        if ($restored === 0) {
                            try {
                                DB::table('company_district_admins')->insert([
                                    'company_id'=>$co->id,'district_id'=>$districtId,'user_id'=>$userId,'status'=>1,
                                    'created_by'=>$uid,'updated_by'=>$uid,'created_at'=>now(),'updated_at'=>now()
                                ]);
                            } catch (\Illuminate\Database\QueryException $qe) {
                                if (strpos($qe->getMessage(), 'Another active District Admin') !== false) {
                                    $conf = DB::table('company_district_admins as t')
                                        ->leftJoin('users as u','u.id','=','t.user_id')
                                        ->where('t.company_id',$co->id)->where('t.district_id',$districtId)->where('t.status',1)
                                        ->first(['t.user_id as admin_user_id','u.name as admin_name']);
                                    $payload = $this->roleTakenPayload('DISTRICT_TAKEN','District Admin',$conf->admin_user_id ?? null,$conf->admin_name ?? null,[
                                        'district_id'=>$districtId
                                    ]);
                                    throw new \Exception(json_encode($payload), 422);
                                }
                                throw $qe;
                            }
                        }
                        $setRole(6);
                    }
                }
                elseif ($roleId === 7) {
                    if ($districtId < 1 || $clusterId < 1) throw new \RuntimeException('District & Cluster required');
                    $occ = DB::table('cluster_masters')
                        ->where('company_id',$co->id)->where('id',$clusterId)
                        ->lockForUpdate()->first(['cluster_supervisor_id']);
                    if (!empty($occ->cluster_supervisor_id) && (int)$occ->cluster_supervisor_id !== $userId) {
                        $u = DB::table('users')->where('id',$occ->cluster_supervisor_id)->first();
                        $payload = $this->roleTakenPayload('CLUSTER_TAKEN','Cluster Admin', (int)$occ->cluster_supervisor_id, $u->name ?? null, [
                            'cluster_id'=>$clusterId
                        ]);
                        throw new \Exception(json_encode($payload), 422);
                    }
                    $restored = DB::table('cluster_members')
                        ->where('company_id',$co->id)->where('cluster_id',$clusterId)->where('user_id',$userId)->where('status',0)
                        ->update(['status'=>1,'updated_by'=>$uid,'updated_at'=>now()]);
                    if ($restored === 0) {
                        $exists = DB::table('cluster_members')
                            ->where('company_id',$co->id)->where('cluster_id',$clusterId)->where('user_id',$userId)->exists();
                        if (!$exists) {
                            DB::table('cluster_members')->insert([
                                'company_id'=>$co->id,
                                'cluster_id'=>$clusterId,
                                'user_id'   =>$userId,
                                'status'    =>1,
                                'created_by'=>$uid,
                                'updated_by'=>$uid,
                                'created_at'=>now(),
                                'updated_at'=>now()
                            ]);
                        } else {
                            DB::table('cluster_members')
                                ->where('company_id',$co->id)->where('cluster_id',$clusterId)->where('user_id',$userId)
                                ->update(['status'=>1,'updated_by'=>$uid,'updated_at'=>now()]);
                        }
                    }
                    DB::table('cluster_masters')->where('company_id',$co->id)->where('id',$clusterId)
                        ->update(['cluster_supervisor_id'=>$userId,'updated_by'=>$uid,'updated_at'=>now()]);
                    $setRole(7);
                }
                else { // roleId === 8
                    if ($districtId < 1 || $clusterId < 1) throw new \RuntimeException('District & Cluster required');
                    $active = DB::table('cluster_members')
                        ->where('company_id',$co->id)->where('cluster_id',$clusterId)->where('user_id',$userId)->where('status',1)
                        ->exists();
                    if ($active) {
                        throw new \Exception(json_encode([
                            'ok'=>false,'code'=>'ALREADY_MEMBER','msg'=>'Already an active member of this cluster'
                        ]), 422);
                    }
                    $restored = DB::table('cluster_members')
                        ->where('company_id',$co->id)->where('cluster_id',$clusterId)->where('user_id',$userId)->where('status',0)
                        ->update(['status'=>1,'updated_by'=>$uid,'updated_at'=>now()]);
                    if ($restored === 0) {
                        $exists = DB::table('cluster_members')
                            ->where('company_id',$co->id)->where('cluster_id',$clusterId)->where('user_id',$userId)->exists();
                        if (!$exists) {
                            DB::table('cluster_members')->insert([
                                'company_id'=>$co->id,'cluster_id'=>$clusterId,'user_id'=>$userId,'status'=>1,
                                'created_by'=>$uid,'updated_by'=>$uid,'created_at'=>now(),'updated_at'=>now()
                            ]);
                        } else {
                            DB::table('cluster_members')
                                ->where('company_id',$co->id)->where('cluster_id',$clusterId)->where('user_id',$userId)
                                ->update(['status'=>1,'updated_by'=>$uid,'updated_at'=>now()]);
                        }
                    }
                    $setRole(8);
                }

                DB::table('registration_master')->where('id',$rm->id)->update([
                    'status'=>1,'approval_status'=>'approved','updated_by'=>$uid,'updated_at'=>now()
                ]);
            });

            $this->logActivity('role_assign',$uid,$co->id,['reg_id'=>$regId,'role_id'=>$roleId]);
            return response()->json(['ok'=>true,'msg'=>'Role assignment saved']);

        } catch (\Illuminate\Database\QueryException $e) {
            $msg = $e->getMessage();
            if (strpos($msg,'Another active Division Admin') !== false) {
                $divisionId = (int)$req->input('division_id', 0);
                $conf = DB::table('company_division_admins as t')
                    ->leftJoin('users as u','u.id','=','t.user_id')
                    ->where('t.company_id',$co->id)->where('t.division_id',$divisionId)->where('t.status',1)
                    ->first(['t.user_id as admin_user_id','u.name as admin_name']);
                return response()->json(
                    $this->roleTakenPayload('DIVISION_TAKEN','Division Admin',$conf->admin_user_id ?? null,$conf->admin_name ?? null,[
                        'division_id'=>$divisionId
                    ]), 422
                );
            }
            if (strpos($msg,'Another active District Admin') !== false) {
                $districtId = (int)$req->input('district_id', 0);
                $conf = DB::table('company_district_admins as t')
                    ->leftJoin('users as u','u.id','=','t.user_id')
                    ->where('t.company_id',$co->id)->where('t.district_id',$districtId)->where('t.status',1)
                    ->first(['t.user_id as admin_user_id','u.name as admin_name']);
                return response()->json(
                    $this->roleTakenPayload('DISTRICT_TAKEN','District Admin',$conf->admin_user_id ?? null,$conf->admin_name ?? null,[
                        'district_id'=>$districtId
                    ]), 422
                );
            }
            $this->qxLog('RMGM.roleAssign', $e);
            return response()->json(['ok'=>false,'msg'=>'Constraint or DB error'], 422);

        } catch (\Exception $e) {
            $payload = json_decode($e->getMessage(), true);
            if (is_array($payload) && isset($payload['code'])) {
                return response()->json($payload, 422);
            }
            return response()->json(['ok'=>false,'msg'=>$e->getMessage()], 422);
        }
    }

    /* ============ Promotion (stored procedure) ============ */
    public function promote(Request $req, string $company, int $regId)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);
        $uid = $this->currentUserId();

        $rm = DB::table('registration_master')->where('id',$regId)->where('company_id',$co->id)->whereNull('deleted_at')->first();
        abort_unless($rm, 404);

        if ($rm->registration_type !== 'company_officer') {
            return response()->json(['ok'=>false,'msg'=>'Promotion allowed only for company_officer'],422);
        }
        if ($rm->approval_status !== 'approved') {
            return response()->json(['ok'=>false,'msg'=>'Promotion requires Approved status'],422);
        }

        $userId = (int)$rm->user_id;
        if ($userId<1) return response()->json(['ok'=>false,'msg'=>'No linked user'],422);

        $user = DB::table('users')->where('id',$userId)->first();
        if (!$user) return response()->json(['ok'=>false,'msg'=>'User not found'],422);

        $currentRoleId = (int)($user->role_id ?? 0);
        if ($currentRoleId < 2 || $currentRoleId > 8) {
            return response()->json(['ok'=>false,'msg'=>'Current role is not in promotable ladder (2..8)'],422);
        }

        $target     = (int)$req->input('target_role_id');
        $divisionId = (int)$req->input('division_id', 0);
        $districtId = (int)$req->input('district_id', 0);
        $clusterId  = (int)$req->input('cluster_id', 0);

        if ($target === 0) {
            return response()->json([
                'ok'=>false,'code'=>'PROMOTE_NEEDS_SELECTION',
                'msg'=>'Select a higher target role.'
            ], 422);
        }

        if ($target >= $currentRoleId) {
            return response()->json(['ok'=>false,'msg'=>'Invalid target: target role must be higher than current (numerically smaller).'],422);
        }

        try {
            DB::statement('CALL sp_promote_user_role(?,?,?,?,?,?,?,?)', [
                $co->id, $regId, $userId, $target, $divisionId, $districtId, $clusterId, $uid
            ]);

            $this->logActivity('promotion',$uid,$co->id,[
                'reg_id'=>$regId,'user_id'=>$userId,'target_role_id'=>$target,
                'division_id'=>$divisionId,'district_id'=>$districtId,'cluster_id'=>$clusterId
            ]);

            return response()->json(['ok'=>true,'msg'=>'Promotion completed']);
        } catch (\Illuminate\Database\QueryException $e) {
            $this->qxLog('RMGM.promote', $e);
            $raw = $e->errorInfo[2] ?? $e->getMessage();
            $safe = trim(preg_replace('/\s+/', ' ', (string)$raw));
            if ($safe === '') { $safe = 'Validation/constraint error during promotion.'; }
            return response()->json(['ok'=>false,'code'=>'PROC_ERR','msg'=>$safe], 422);
        } catch (\Throwable $e) {
            return response()->json(['ok'=>false,'msg'=>$e->getMessage()], 422);
        }
    }

    /* ============ PDF / Export ============ */

    private function orderBySafe($qb, string $table)
    {
        if (Schema::hasColumn($table, 'created_at')) return $qb->orderByDesc('created_at');
        if (Schema::hasColumn($table, 'updated_at')) return $qb->orderByDesc('updated_at');
        if (Schema::hasColumn($table, 'id'))        return $qb->orderByDesc('id');
        return $qb;
    }

    public function pdf(Request $req, string $company, int $id)
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);

        // ===== Master =====
        $rm = DB::table('registration_master as rm')
            ->leftJoin('users as u','u.id','=','rm.user_id')
            ->leftJoin('professions as pf','pf.id','=','rm.Profession')
            ->leftJoin('divisions as dv','dv.id','=','rm.division_id')
            ->leftJoin('districts as ds','ds.id','=','rm.district_id')
            ->leftJoin('upazilas as uz','uz.id','=','rm.upazila_id')
            ->leftJoin('thanas as th','th.id','=','rm.thana_id')
            ->where('rm.company_id',$co->id)
            ->where('rm.id',$id)
            ->first([
                'rm.*',
                'u.name  as user_name',
                'u.email as user_email',
                'u.role_id as user_role_id',
                'pf.profession as profession_name',
                'dv.name as division_name',
                'ds.name as district_name',
                'uz.name as upazila_name',
                'th.name as thana_name',
            ]);
        abort_unless($rm, 404);

        $guess = trim((string)($rm->full_name ?? ''));
        if ($guess === '' && property_exists($rm,'First_Name')) {
            $guess = trim(($rm->First_Name ?? '').' '.($rm->Last_Name ?? ''));
        }
        if ($guess === '') $guess = (string)($rm->user_name ?? '');
        $rm->Full_Name       = $guess;

        $rm->Gender          = $rm->gender ?? null;
        $rm->DOB             = $rm->date_of_birth ?? null;
        $rm->Phone           = $rm->phone ?? null;
        $rm->Email           = $rm->email ?? null;
        $rm->Present_Address = $rm->present_address ?? null;
        $rm->Notes           = $rm->notes ?? null;

        $roleRow = null;
        if (!empty($rm->user_role_id)) {
            $roleRow = DB::table('roles')->where('id', $rm->user_role_id)->first(['id','name']);
        }

        // ===== Education =====
        $education = DB::table('education_background as e')
            ->leftJoin('degrees as dg','dg.id','=','e.degree_id')
            ->where('e.Company_id',$co->id)
            ->where('e.registration_id',$id)
            ->where('e.status',1)
            ->orderBy('e.id')
            ->get([
                'e.id',
                DB::raw('dg.name as degree_name'),
                DB::raw('e.Institution as institute_name'),
                DB::raw('e.Passing_Year as passing_year'),
                DB::raw('e.Result_Type as result_type'),
                DB::raw('e.obtained_grade_or_score as result'),
                DB::raw('e.Out_of as out_of'),
            ]);

        // ===== Job Experiences =====
        $job_experiences = DB::table('job_experiences as j')
            ->where('j.Company_id',$co->id)
            ->where('j.registration_id',$id)
            ->where('j.status',1)
            ->orderBy('j.id')
            ->get([
                'j.id',
                DB::raw('j.Employer as employer'),
                DB::raw('j.Job_title as designation'),
                DB::raw('j.Joining_date as start_date'),
                DB::raw('j.End_date as end_date'),
                'j.is_present_job',
            ]);

        // ===== Software Expertise =====
        $software_expertise = DB::table('expertise_on_softwares as ex')
            ->leftJoin('software_list as sl','sl.id','=','ex.expert_on_software')
            ->where('ex.Company_id',$co->id)
            ->where('ex.registration_id',$id)
            ->where('ex.status',1)
            ->orderBy('ex.id')
            ->get([
                'ex.id',
                DB::raw('sl.software_name as software_name'),
                'ex.experience_in_years',
            ]);

        // ===== Skills =====
        $skills = DB::table('person_skills as ps')
            ->leftJoin('skills as sk','sk.id','=','ps.skill')
            ->where('ps.Company_id',$co->id)
            ->where('ps.registration_id',$id)
            ->where('ps.status',1)
            ->orderBy('ps.id')
            ->get([
                'ps.id',
                DB::raw('sk.skill as skill_name'),
            ]);

        // ===== Preferred Areas =====
        $preferred_areas = DB::table('preffered_area_of_job as p')
            ->leftJoin('tasks_param as tp','tp.Task_Param_ID','=','p.Task_Param_ID')
            ->where('p.Company_id',$co->id)
            ->where('p.registration_id',$id)
            ->where('p.status',1)
            ->orderBy('p.Task_Param_ID')
            ->get([
                DB::raw('p.Task_Param_ID as task_param_id'),
                DB::raw('tp.Task_Param_Name as task_param_name'),
            ]);

        // ===== Training Required =====
        $training_required = DB::table('training_required as tr')
            ->leftJoin('training_category as tc', function($j){
                $j->on('tc.Company_id','=','tr.Company_id')
                ->on('tc.Training_Category_Id','=','tr.Training_Category_Id');
            })
            ->leftJoin('training_list as tl', function($j){
                $j->on('tl.Company_id','=','tr.Company_id')
                ->on('tl.Training_Category_Id','=','tr.Training_Category_Id')
                ->on('tl.Training_ID','=','tr.Training_ID');
            })
            ->where('tr.Company_id',$co->id)
            ->where('tr.registration_id',$id)
            ->where('tr.status',1)
            ->orderBy('tr.Training_Category_Id')
            ->orderBy('tr.Training_ID')
            ->get([
                DB::raw('tr.Training_Category_Id as training_category_id'),
                DB::raw('tr.Training_ID as training_id'),
                DB::raw('tc.Training_Category_Name as training_category_name'),
                DB::raw('tl.Training_Name as training_name'),
            ]);

        $sections = [
            'education'          => 'Education Background',
            'job_experiences'    => 'Job Experiences',
            'software_expertise' => 'Expertise on Softwares',
            'skills'             => 'Skills',
            'preferred_areas'    => 'Preferred Areas of Job',
            'training_required'  => 'Training Required',
        ];

        return response()->view('registration.Registration_Management.pdf_resume', [
            'registration'        => $rm,
            'education'           => $education,
            'job_experiences'     => $job_experiences,
            'software_expertise'  => $software_expertise,
            'skills'              => $skills,
            'preferred_areas'     => $preferred_areas,
            'training_required'   => $training_required,
            'sections'            => $sections,
            'roleRow'             => $roleRow,
        ]);
    }


    public function exportCsv(Request $req, string $company): StreamedResponse
    {
        $co = $this->resolveCompany($company); abort_unless($co, 404);

        $fh = fopen('php://output','w');
        $callback = function() use ($co,$fh){
            fputcsv($fh, ['ID','User','Phone','Type','Division','District','Upazila','Approval','Role','Status']);
            $rows = DB::table('registration_master as rm')
                ->leftJoin('users as u','u.id','=','rm.user_id')
                ->leftJoin('roles as r','r.id','=','u.role_id')
                ->leftJoin('divisions as dv','dv.id','=','rm.division_id')
                ->leftJoin('districts as ds','ds.id','=','rm.district_id')
                ->leftJoin('upazilas as uz','uz.id','=','rm.upazila_id')
                ->where('rm.company_id',$co->id)->whereNull('rm.deleted_at')
                ->orderByDesc('rm.id')->limit(5000)
                ->get([
                    'rm.id','u.name','rm.phone','rm.registration_type',
                    'dv.name as dv','ds.name as ds','uz.name as uz',
                    'rm.approval_status','r.name as role','rm.status'
                ]);

            foreach($rows as $r){
                fputcsv($fh, [
                    $r->id, $r->name, $r->phone, $r->registration_type,
                    $r->dv, $r->ds, $r->uz,
                    $r->approval_status, $r->role,
                    $r->status? 'Active':'Inactive'
                ]);
            }
            fflush($fh);
        };

        return response()->stream($callback, 200, [
            'Content-Type'=>'text/csv',
            'Content-Disposition'=>'attachment; filename="registrations.csv"'
        ]);
    }
}
