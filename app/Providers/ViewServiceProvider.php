<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('backend.layouts.partials.header', function ($view) {
            // --- Auth (real first, then forced via .env) -------------------------
            $forcedId = config('header.dev_force_user_id');
            $forcedId = is_numeric($forcedId) ? (int) $forcedId : null;

            $uid     = Auth::id() ?? $forcedId;
            $isGuest = ($uid === null);

            $headerUser    = null;
            $menuTree      = [];
            $headerCompany = null;

            if (!$isGuest) {
                $headerUser = DB::table('users')->where('id', $uid)->first();
                if (!$headerUser) {
                    $isGuest = true;
                }
            }

            // --- Company (from route slug or model; then user/role fallback) -----
            $route = request()->route();
            if ($route) {
                $param = $route->parameter('company'); // Company model or slug
                if ($param instanceof \App\Models\SuperAdmin\GlobalSetup\Company) {
                    $headerCompany = DB::table('companies')
                        ->where('id', $param->id)->where('status', 1)->whereNull('deleted_at')->first();
                } elseif (is_string($param) && $param !== '') {
                    $headerCompany = DB::table('companies')
                        ->where('slug', $param)->where('status', 1)->whereNull('deleted_at')->first();
                }
            }
            if (!$headerCompany && !$isGuest) {
                $userCompanyId = (int) ($headerUser->company_id ?? 0);
                if ($userCompanyId > 0) {
                    $headerCompany = DB::table('companies')
                        ->where('id', $userCompanyId)->where('status', 1)->whereNull('deleted_at')->first();
                } else {
                    $roleId = (int) ($headerUser->role_id ?? 0);
                    if ($roleId > 0) {
                        $roleCompanyId = (int) DB::table('roles')->where('id', $roleId)->value('company_id');
                        if ($roleCompanyId > 0) {
                            $headerCompany = DB::table('companies')
                                ->where('id', $roleCompanyId)->where('status', 1)->whereNull('deleted_at')->first();
                        }
                    }
                }
            }

            // Canonicalize branding to logged-in user's tenant, if any
            if (!$isGuest) {
                $userCompanyId = (int) ($headerUser->company_id ?? 0);
                if ($userCompanyId > 0) {
                    $userCompany = DB::table('companies')
                        ->where('id', $userCompanyId)->where('status', 1)->whereNull('deleted_at')->first();
                    if ($userCompany) {
                        $headerCompany = $userCompany;
                    }
                } else {
                    $roleId = (int) ($headerUser->role_id ?? 0);
                    if ($roleId > 0) {
                        $roleCompanyId = (int) DB::table('roles')->where('id', $roleId)->value('company_id');
                        if ($roleCompanyId > 0) {
                            $roleCompany = DB::table('companies')
                                ->where('id', $roleCompanyId)->where('status', 1)->whereNull('deleted_at')->first();
                            if ($roleCompany) {
                                $headerCompany = $roleCompany;
                            }
                        }
                    }
                }
            }

            // --- Per-request URL default for {company} ---------------------------
            // Ensures route('registration.mgmt.index') works without manually passing ['company'=>...]
            $companyDefault = null;
            if (!empty($headerCompany)) {
                $companyDefault = $headerCompany->slug ?? ($headerCompany->id ?? null);
            }
            if (!$companyDefault && isset($route)) {
                $param = $route->parameter('company'); // model, slug, or id
                if ($param instanceof \App\Models\SuperAdmin\GlobalSetup\Company) {
                    $companyDefault = $param->slug ?? $param->id;
                } elseif (is_string($param) || is_numeric($param)) {
                    $companyDefault = $param;
                }
            }
            if ($companyDefault) {
                URL::defaults(['company' => $companyDefault]);
            }

            $brandName = $headerCompany->name ?? 'ArchReach';
            $companyId = $headerCompany->id ?? (($headerUser->company_id ?? null) ?: null);

            // --- Registration state (DB-first if table exists) -------------------
            $hasReg = false;            // any row in registration_master for this user (and optionally company)
            $regApproved = false;       // status == 1
            $regStatus = null;          // 0/1/2/3
            $approvalStatus = null;     // pending/approved/declined

            if (!$isGuest) {
                // Prefer configured table; fallback to registration_master if present
                $table      = config('header.registration.table') ?: 'registration_master';
                $userCol    = config('header.registration.user_column', 'user_id');
                $companyCol = config('header.registration.company_column') ?: 'company_id';

                if (Schema::hasTable($table)) {
                    $q = DB::table($table)->where($userCol, $uid);

                    if ($companyId && Schema::hasColumn($table, $companyCol)) {
                        $q->where($companyCol, $companyId);
                    }

                    $row = $q->select(['status','approval_status'])->orderByDesc('id')->first();

                    if ($row) {
                        $hasReg = true;
                        $regStatus = is_null($row->status) ? null : (int) $row->status;
                        $approvalStatus = $row->approval_status ?? null;
                        $regApproved = ($regStatus === 1);
                    }
                } else {
                    // Fallback legacy env simulator if table missing
                    $regApproved = (bool) config('header.dev_force_registered');
                    $hasReg = $regApproved; // best-effort when table absent
                }
            }

            // --- Menus appear only when authenticated AND approved (status=1) ----
            if (!$isGuest && $regApproved) {
                $roleId = (int) ($headerUser->role_id ?? 0);
                if ($roleId > 0) {
                    $menus = DB::table('menus as m')
                        ->join('role_menu_mappings as rmm', 'rmm.menu_id', '=', 'm.id')
                        ->where('rmm.role_id', $roleId)
                        ->whereNull('m.deleted_at')
                        ->whereNull('rmm.deleted_at')
                        ->select('m.id','m.parent_id','m.name','m.uri','m.icon','m.menu_order')
                        ->orderBy('m.menu_order')
                        ->get()
                        ->map(function ($m) {
                            try {
                                $m->url = ($m->uri && Route::has($m->uri)) ? route($m->uri) : '#';
                            } catch (\Throwable $e) {
                                $m->url = '#';
                            }
                            return $m;
                        });

                    $byParent = [];
                    foreach ($menus as $m) {
                        $byParent[$m->parent_id ?? 0][] = $m;
                    }
                    foreach (($byParent[0] ?? []) as $parent) {
                        $children = $byParent[$parent->id] ?? [];
                        $menuTree[] = (object) [
                            'id'       => $parent->id,
                            'name'     => $parent->name,
                            'icon'     => $parent->icon,
                            'uri'      => $parent->uri,
                            'url'      => $parent->url,
                            'children' => $children,
                        ];
                    }
                }
            }

            // --- Header UI flags -------------------------------------------------
            $ui = (object) [
                'loginVisible'   => true,
                'loginEnabled'   => true,
                'logoutVisible'  => true,
                'logoutEnabled'  => !$isGuest,

                // NEW: Register vs Edit Registration is decided by hasReg
                'registerVisible' => true,                 // may be visible but disabled for guests
                'registerEnabled' => (!$isGuest && !$hasReg),
                'editRegVisible'  => (!$isGuest && $hasReg),
                'editRegEnabled'  => (!$isGuest && $hasReg),

                'toastMessage'    => null,
            ];

            if ($isGuest) {
                // Guest: prompt to login
                $ui->registerEnabled = false;
                $ui->toastMessage    = "Welcome, To {$brandName}. Pls, Login";
            } else {
                if (!$hasReg) {
                    // Logged-in, no registration yet → prompt to register
                    $ui->toastMessage = "Pls, Register to access the Menus and Services";
                } elseif (!$regApproved) {
                    // Logged-in, registration exists but not approved/active
                    // Map status/approval_status to a helpful message
                    $msg = "Registration submitted.";
                    if ($regStatus === 0 || $approvalStatus === 'pending') {
                        $msg = "Your registration is pending approval. You can edit your registration.";
                    } elseif ($regStatus === 2 || $approvalStatus === 'declined') {
                        $msg = "Your registration was declined. Please edit and resubmit.";
                    } elseif ($regStatus === 3) {
                        $msg = "Your registration is inactive. You may edit or contact support.";
                    }
                    $ui->toastMessage = $msg;
                } else {
                    // Approved/active → usually no toast
                    $ui->toastMessage = null;
                }
            }

            $view->with(compact('isGuest', 'headerUser', 'menuTree', 'headerCompany', 'ui'));
        });
    }
}
