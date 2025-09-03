<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SuperAdmin\GlobalSetup\Company;

class DashboardController extends Controller
{
    /** Helper: current user id (Auth first, then forced from config) */
    private function currentUserId(): ?int
    {
        $forced = config('header.dev_force_user_id'); // null or int
        return Auth::id() ?? $forced;
    }

    /**
     * Public landing (guest) — tenant-aware.
     * If a user exists, immediately redirect to the correct index within the user's tenant.
     *
     * Example:
     *   If user belongs to Trimatric Global but opens /company/abc-limited/dashboard/public,
     *   we redirect them to /company/trimatric-global/dashboard to keep tenant context canonical.
     */
    public function public(?Company $company = null)
    {
        $uid = $this->currentUserId();

        if ($uid) {
            // --- Canonicalize to user's own tenant ------------------------------
            $user = DB::table('users')->where('id', $uid)->first();
            $targetSlug = null;

            if ($user) {
                $userCompanyId = (int) ($user->company_id ?? 0);
                if ($userCompanyId > 0) {
                    $targetSlug = DB::table('companies')->where('id', $userCompanyId)->value('slug');
                } else {
                    // Optional: fallback via role -> company_id
                    $roleId = (int) ($user->role_id ?? 0);
                    if ($roleId > 0) {
                        $roleCompanyId = (int) DB::table('roles')->where('id', $roleId)->value('company_id');
                        if ($roleCompanyId > 0) {
                            $targetSlug = DB::table('companies')->where('id', $roleCompanyId)->value('slug');
                        }
                    }
                }
            }

            return $targetSlug
                ? redirect()->route('backend.company.dashboard.index', ['company' => $targetSlug])
                : redirect()->route('backend.dashboard.index');
        }

        // Guest → show public landing; header will brand via slug (if any)
        return view('backend.dashboard.public', [
            'title' => $company ? ($company->name . ' | Dashboard') : 'Welcome | Dashboard',
        ]);
    }

    /**
     * Role-aware dashboard index — tenant-aware.
     * If no user, go to the matching public page in SAME context.
     * If authenticated, ensure the URL's tenant matches the user's tenant (canonicalize).
     */
    public function index(?Company $company = null)
    {
        $uid = $this->currentUserId();

        // No user → send to public (keep tenant/global context)
        if (!$uid) {
            return $company
                ? redirect()->route('backend.company.dashboard.public', ['company' => $company->slug])
                : redirect()->route('backend.dashboard.public');
        }

        // Pull user & role_type
        $user = DB::table('users')->where('id', $uid)->first();
        if (!$user) {
            return $company
                ? redirect()->route('backend.company.dashboard.public', ['company' => $company->slug])
                : redirect()->route('backend.dashboard.public');
        }

        // --- Canonicalize tenant context for authenticated users -----------------
        // If URL has a company slug that differs from the user's company slug, redirect to user's tenant.
        // If URL has no slug but user has a tenant, redirect to that tenant.
        $userCompanySlug = null;
        $userCompanyId = (int) ($user->company_id ?? 0);
        if ($userCompanyId > 0) {
            $userCompanySlug = DB::table('companies')->where('id', $userCompanyId)->value('slug');
        } else {
            // Optional: fallback via role -> company_id
            $roleId = (int) ($user->role_id ?? 0);
            if ($roleId > 0) {
                $roleCompanyId = (int) DB::table('roles')->where('id', $roleId)->value('company_id');
                if ($roleCompanyId > 0) {
                    $userCompanySlug = DB::table('companies')->where('id', $roleCompanyId)->value('slug');
                }
            }
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

        // Determine role type view as you already do
        $role = DB::table('roles')->where('id', $user->role_id)->first();
        $roleType = null;
        if ($role) {
            $roleType = DB::table('role_types')->where('id', $role->role_type_id)->value('name');
        }

        // Normalize role type → key
        $key = $roleType
            ? strtolower(str_replace([' ', '_'], ['-', '-'], $roleType))
            : 'guest';

        // Map role_type key → view
        $map = [
            'super-admin' => 'backend.dashboard.super-admin.index',
            'ceo'         => 'backend.dashboard.ceo.index',
            'zonal-admin' => 'backend.dashboard.zonal-admin.index',
            // defaults
            'guest'       => 'backend.dashboard.public',
        ];

        $view = $map[$key] ?? $map['guest'];

        // If somehow we still landed on a "guest" view while having a user, fallback:
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
