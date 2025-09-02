<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class ViewServiceProvider extends ServiceProvider
{
    // nothing to register now
    public function register(): void {}

    /**
     * I only compose the header partial so other pages don’t do extra work.
     *
     * Exposes to the header view:
     *  - $isGuest        : true when there is no auth user and no forced id
     *  - $headerUser     : current (or emulated) user row (from users)
     *  - $menuTree       : role-based parent/child menus
     *  - $headerCompany  : company row to brand the header (from URL slug if present;
     *                      otherwise from the user’s company; otherwise null)
     *
     * Rules for $headerCompany:
     *  - If the current route has {company} param → find by companies.slug (status=1)
     *  - else if I have a user → company via users.company_id (or via roles.company_id)
     *  - else → null (show platform brand)
     */
    public function boot(): void
    {
        View::composer('backend.layouts.partials.header', function ($view) {
            // decide current user id: prefer Auth, else use forced id from config/header.php
            $forcedId   = config('header.dev_force_user_id');   // int|null (from .env HEADER_FORCE_USER_ID)
            $authUserId = Auth::id();                           // int|null
            $currentId  = $authUserId ?? $forcedId;             // null → guest
            $isGuest    = ($currentId === null);

            $headerUser = null;
            $menuTree   = [];
            $headerCompany = null;

            // Load user if any
            if (!$isGuest) {
                $headerUser = DB::table('users')->where('id', $currentId)->first();
                if (!$headerUser) {
                    $isGuest = true;
                }
            }

            // Resolve $headerCompany (prefer URL slug or bound model)
            $headerCompany = null;
            $route = request()->route();

            if ($route) {
                $param = $route->parameter('company'); // could be a string slug OR a bound Company model

                if ($param instanceof \App\Models\SuperAdmin\GlobalSetup\Company) {
                    // Use the bound model (only if active & not deleted)
                    $headerCompany = DB::table('companies')
                        ->where('id', $param->id)
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->first();
                } elseif (is_string($param) && $param !== '') {
                    // Use the slug
                    $headerCompany = DB::table('companies')
                        ->where('slug', $param)
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->first();
                }
            }

            // If still no company from URL, derive from user (company_id → companies)
            if (!$headerCompany && !$isGuest) {
                $userCompanyId = (int) ($headerUser->company_id ?? 0);
                if ($userCompanyId > 0) {
                    $headerCompany = DB::table('companies')
                        ->where('id', $userCompanyId)
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->first();
                } else {
                    // optional: fallback via role → company_id
                    $roleId = (int) ($headerUser->role_id ?? 0);
                    if ($roleId > 0) {
                        $roleCompanyId = (int) DB::table('roles')->where('id', $roleId)->value('company_id');
                        if ($roleCompanyId > 0) {
                            $headerCompany = DB::table('companies')
                                ->where('id', $roleCompanyId)
                                ->where('status', 1)
                                ->whereNull('deleted_at')
                                ->first();
                        }
                    }
                }
            }

            // Build role-based menu tree when I have a valid user
            if (!$isGuest) {
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

                    $menuTree = [];
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
                } else {
                    $isGuest = true;
                }
            }

            $view->with(compact('isGuest', 'headerUser', 'menuTree', 'headerCompany'));
        });
    }
}
