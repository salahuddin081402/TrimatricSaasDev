<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

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
            // Example URLs:
            //   /backend/company/abc-limited/dashboard/public
            //   /backend/company/trimatric-global/dashboard
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
                // If no slug/company from URL, try user's own company, then role->company
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

            // --- Canonicalize branding for authenticated users -------------------
            // Requirement:
            //  If a logged-in (forced or real) user opens another tenant's URL
            //  (e.g., /abc-limited/...), the header MUST reflect the user's actual
            //  company (e.g., Trimatric Global). This override keeps header branding
            //  consistent with the authenticated identity.
            if (!$isGuest) {
                $userCompanyId = (int) ($headerUser->company_id ?? 0);
                if ($userCompanyId > 0) {
                    $userCompany = DB::table('companies')
                        ->where('id', $userCompanyId)->where('status', 1)->whereNull('deleted_at')->first();

                    if ($userCompany) {
                        $headerCompany = $userCompany; // override any slug-derived company
                    }
                } else {
                    // Optional: fallback via role -> company_id (keeps your current behavior)
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

            // Brand name for dynamic messages (fallback = ArchReach)
            $brandName = $headerCompany->name ?? 'ArchReach';
            $companyId = $headerCompany->id ?? (($headerUser->company_id ?? null) ?: null);

            // --- Registration state (config/header.php driven) -------------------
            // Works now with IS_FORCED_REGISTRATION, and later with your real table
            $isRegistered = false;
            if (!$isGuest) {
                $src        = config('header.registration.source', 'auto');
                $envReg     = (bool) config('header.dev_force_registered');

                $table      = config('header.registration.table');
                $userCol    = config('header.registration.user_column', 'user_id');
                $statusCol  = config('header.registration.status_column', 'status');
                $activeVal  = config('header.registration.status_active', '1');
                $companyCol = config('header.registration.company_column');

                $hasTable   = ($table && Schema::hasTable($table));

                if ($src === 'env') {
                    $isRegistered = $envReg;
                } else {
                    if ($hasTable) {
                        $q = DB::table($table);

                        if (Schema::hasColumn($table, 'deleted_at')) {
                            $q->whereNull('deleted_at');
                        }
                        if ($userCol && Schema::hasColumn($table, $userCol)) {
                            $q->where($userCol, $uid);
                        }
                        if ($companyCol && $companyId && Schema::hasColumn($table, $companyCol)) {
                            $q->where($companyCol, $companyId);
                        }
                        if ($statusCol && Schema::hasColumn($table, $statusCol)) {
                            $q->where($statusCol, $activeVal);
                        }

                        $isRegistered = $q->exists();

                        if (!$isRegistered && $src === 'auto') {
                            $isRegistered = $envReg; // graceful fallback while DB evolves
                        }
                    } else {
                        $isRegistered = $envReg;
                    }
                }
            }

            // --- Menus appear only when authenticated AND registered -------------
            if (!$isGuest && $isRegistered) {
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
                            $m->url = ($m->uri && Route::has($m->uri)) ? route($m->uri) : '#';
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

            // --- Header UI flags (and dynamic toasts using $brandName) -----------
            $ui = (object) [
                'loginVisible'   => true,
                'loginEnabled'   => true,
                'logoutVisible'  => true,
                'logoutEnabled'  => false,

                'registerVisible' => false,
                'registerEnabled' => false,
                'editRegVisible'  => false,
                'editRegEnabled'  => false,

                'toastMessage'    => null,
            ];

            if ($isGuest) {
                // Guest: Register visible but disabled, Login enabled
                $ui->loginEnabled     = true;
                $ui->logoutEnabled    = false;

                $ui->registerVisible  = true;
                $ui->registerEnabled  = false;

                $ui->editRegVisible   = false;
                $ui->editRegEnabled   = false;

                // Dynamic brand in welcome message (e.g., "Trimatric Global" or "ABC Limited")
                $ui->toastMessage     = "Welcome, To {$brandName}. Pls, Login";
            } else {
                // Authenticated (forced or real)
                $ui->loginEnabled     = false;
                $ui->logoutEnabled    = true;

                if ($isRegistered) {
                    $ui->editRegVisible  = true;
                    $ui->editRegEnabled  = true;
                } else {
                    $ui->registerVisible = true;
                    $ui->registerEnabled  = true;

                    $ui->toastMessage    = "Pls, Register to access the Menus and Services";
                }
            }

            $view->with(compact('isGuest', 'headerUser', 'menuTree', 'headerCompany', 'ui'));
        });
    }
}
