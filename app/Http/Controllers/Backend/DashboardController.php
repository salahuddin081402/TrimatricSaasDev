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
     * If a user exists, immediately redirect to the correct index within the SAME context (tenant/global).
     */
    public function public(?Company $company = null)
    {
        $uid = $this->currentUserId();

        if ($uid) {
            // User exists → go to index in same scope
            return $company
                ? redirect()->route('backend.company.dashboard.index', ['company' => $company->slug])
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
