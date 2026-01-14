<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\SuperAdmin\GlobalSetup\Company;

class DashboardController extends Controller
{
    /** Helper: current user id (Auth first, then forced from config) */
    private function currentUserId(): ?int
    {
        $forced = config('header.dev_force_user_id');

        // If dev forced user id is set, ALWAYS use it for this module.
        if (is_numeric($forced)) {
            return (int) $forced;
        }

        return Auth::id();
    }

    /** Resolve user's company_id (user->company_id, fallback role->company_id) */
    private function userCompanyId(object $user = null): ?int
    {
        if (!$user) return null;
        $cid = (int) ($user->company_id ?? 0);
        if ($cid > 0) return $cid;

        $roleId = (int) ($user->role_id ?? 0);
        if ($roleId > 0) {
            $roleCompanyId = (int) DB::table('roles')->where('id', $roleId)->value('company_id');
            if ($roleCompanyId > 0) return $roleCompanyId;
        }
        return null;
    }

    /** Registration state helper based on registration_master */
    private function regState(int $uid, ?int $companyId): array
    {
        $table = config('header.registration.table') ?: 'registration_master';
        $state = ['has' => false, 'approved' => false, 'status' => null, 'approval_status' => null];

        if (!Schema::hasTable($table)) {
            // Fallback to env simulator
            $sim = (bool) config('header.dev_force_registered');
            return ['has' => $sim, 'approved' => $sim, 'status' => $sim ? 1 : null, 'approval_status' => $sim ? 'approved' : null];
        }

        $q = DB::table($table)->where('user_id', $uid);
        if ($companyId && Schema::hasColumn($table, 'company_id')) {
            $q->where('company_id', $companyId);
        }

        $row = $q->select(['status','approval_status'])->orderByDesc('id')->first();
        if (!$row) return $state;

        $status = is_null($row->status) ? null : (int) $row->status;
        $approved = ($status === 1);

        return ['has' => true, 'approved' => $approved, 'status' => $status, 'approval_status' => $row->approval_status ?? null];
    }

    /** Is user active in this company (users.status=1 and belongs to company via user.company_id or role.company_id) */
    private function userActiveInCompany(int $uid, ?int $companyId): bool
    {
        if (!$companyId) return false;
        $u = DB::table('users')->where('id', $uid)->first();
        if (!$u) return false;
        if ((int)($u->status ?? 0) !== 1) return false;

        $uCompany = $this->userCompanyId($u);
        return $uCompany && ((int)$uCompany === (int)$companyId);
    }

    /** Has at least one registration of a given type and not declined */
    private function hasNonDeclinedReg(int $uid, int $companyId, string $type): bool
    {
        if (!Schema::hasTable('registration_master')) return false;

        return DB::table('registration_master')
            ->where('user_id', $uid)
            ->where('company_id', $companyId)
            ->where('registration_type', $type)
            ->where(function ($q) {
                $q->whereNull('approval_status')->orWhere('approval_status', '<>', 'declined');
            })
            ->exists();
    }

    /** Build minimal UI flags for header/buttons on public page */
    private function buildUiFlags(?int $uid, ?int $companyId): object
    {
        $isGuest = ($uid === null);

        $editClientVisible  = false;
        $editOfficerVisible = false;
        $editEnterpriseVisible  = false;
        $editEntrepreneurVisible = false;
        $editProfessionalVisible = false;

        if ($uid && $companyId && $this->userActiveInCompany($uid, $companyId)) {
            $editClientVisible  = $this->hasNonDeclinedReg($uid, $companyId, 'client');
            $editOfficerVisible = $this->hasNonDeclinedReg($uid, $companyId, 'company_officer');
            $editProfessionalVisible  = $this->hasNonDeclinedReg($uid, $companyId, 'professional');
            $editEntrepreneurVisible  = $this->hasNonDeclinedReg($uid, $companyId, 'entrepreneur');
            $editEnterpriseVisible    = $this->hasNonDeclinedReg($uid, $companyId, 'enterprise_client');
        }

        // Enabled mirrors Visible for simplicity; keeps backward compatibility
        return (object)[
            'loginVisible'        => true,
            'loginEnabled'        => true,
            'logoutVisible'       => true,
            'logoutEnabled'       => !$isGuest,

            'registerVisible'     => $isGuest || (!$editClientVisible && !$editOfficerVisible),
            'registerEnabled'     => !$isGuest,

            // legacy single-edit flags (kept untouched)
            'editRegVisible'      => $editClientVisible,   // legacy maps to client
            'editRegEnabled'      => $editClientVisible,

            // new explicit flags
            'editClientVisible'   => $editClientVisible,
            'editClientEnabled'   => $editClientVisible,

            'editEnterpriseVisible'   => $editEnterpriseVisible,
            'editEnterpriseEnabled'   => $editEnterpriseVisible,

            'editOfficerVisible'  => $editOfficerVisible,
            'editOfficerEnabled'  => $editOfficerVisible,

            'editEntrepreneurVisible'   => $editEntrepreneurVisible,
            'editEntrepreneurEnabled'   => $editEntrepreneurVisible,

            'editProfessionalVisible'   => $editProfessionalVisible,
            'editProfessionalEnabled'   => $editProfessionalVisible,

        ];
    }

    /**
     * Public landing (guest) — tenant-aware.
     * Logged-in users are redirected to role dashboards ONLY if registration is approved (status=1).
     */
    public function public(?Company $company = null)
    {
        $uid = $this->currentUserId();

        if ($uid) {
            $user = DB::table('users')->where('id', $uid)->first();

            // Determine user's tenant slug
            $targetSlug = null;
            if ($user) {
                $userCompanyId = $this->userCompanyId($user);
                if ($userCompanyId) {
                    $targetSlug = DB::table('companies')->where('id', $userCompanyId)->value('slug');
                }
            }

            // Check registration approval (status=1)
            $companyIdForReg = $user ? $this->userCompanyId($user) : null;
            $reg = $this->regState($uid, $companyIdForReg);

            if ($reg['approved']) {
                return $targetSlug
                    ? redirect()->route('backend.company.dashboard.index', ['company' => $targetSlug])
                    : redirect()->route('backend.dashboard.index');
            }
        }

        // Compute header/public UI flags safely
        $companyIdCtx = $company ? (int)$company->id : null;
        $ui = $this->buildUiFlags($uid, $companyIdCtx);

        // Guest or not-yet-approved user → stay on public landing
        return view('backend.dashboard.public', [
            'title' => $company ? ($company->name . ' | Dashboard') : 'Welcome | Dashboard',
            'ui'    => $ui, // non-breaking: views may ignore it
        ]);
    }

    /**
     * Role-aware dashboard index — tenant-aware.
     * Requires registration approved (status=1). Otherwise, bounce to public.
     */
    public function index(?Company $company = null)
    {
        $uid = $this->currentUserId();

        if (!$uid) {
            return $company
                ? redirect()->route('backend.company.dashboard.public', ['company' => $company->slug])
                : redirect()->route('backend.dashboard.public');
        }

        $user = DB::table('users')->where('id', $uid)->first();
        if (!$user) {
            return $company
                ? redirect()->route('backend.company.dashboard.public', ['company' => $company->slug])
                : redirect()->route('backend.dashboard.public');
        }

        // Canonicalize tenant slug for authenticated users
        $userCompanySlug = null;
        $userCompanyId   = $this->userCompanyId($user);
        if ($userCompanyId) {
            $userCompanySlug = DB::table('companies')->where('id', $userCompanyId)->value('slug');
        }

        if ($company) {
            if ($userCompanySlug && $company->slug !== $userCompanySlug) {
                return redirect()->route('backend.company.dashboard.index', ['company' => $userCompanySlug]);
            }
        } else {
            if ($userCompanySlug) {
                return redirect()->route('backend.company.dashboard.index', ['company' => $userCompanySlug]);
            }
        }

        // Registration must be approved to enter index
        $reg = $this->regState($uid, $userCompanyId);
        if (!$reg['approved']) {
            return $userCompanySlug
                ? redirect()->route('backend.company.dashboard.public', ['company' => $userCompanySlug])
                : redirect()->route('backend.dashboard.public');
        }

        // Determine role type view
        $role = DB::table('roles')->where('id', $user->role_id)->first();
        $roleType = null;
        if ($role) {
            $roleType = DB::table('role_types')->where('id', $role->role_type_id)->value('name');
        }

        $key = $roleType
            ? strtolower(str_replace([' ', '_'], ['-', '-'], $roleType))
            : 'guest';

        // Role-type → view map
        $map = [
            'super-admin'      => 'backend.dashboard.super-admin.index',
            'head-office'      => 'backend.dashboard.head-office.index',
            'business-officers'=> 'backend.dashboard.business-officers.index',
            'client'           => 'backend.dashboard.client.index',
            'professional'     => 'backend.dashboard.professional.index',
            'entrepreneur'     => 'backend.dashboard.entrepreneur.index',
            'enterprise-client'=> 'backend.dashboard.enterprise-client.index',
            'guest'            => 'backend.dashboard.public',
        ];

        $view = $map[$key] ?? $map['guest'];

        // Safety: if somehow guest view while approved, send to super-admin page
        if ($view === 'backend.dashboard.public') {
            $view = 'backend.dashboard.super-admin.index';
        }

        return view($view, [
            'title'    => 'Dashboard',
            'user'     => $user,
            'roleType' => $roleType,
        ]);
    }
}
