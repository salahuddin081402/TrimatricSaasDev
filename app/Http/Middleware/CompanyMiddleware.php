<?php

namespace App\Http\Middleware;

use App\Models\SuperAdmin\GlobalSetup\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CompanyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /**
         * IMPORTANT:
         * - Must not break global routes like /logout (no {company} param).
         * - Only enforce company when the route has {company}.
         */
        $slug = $request->route('company');

        // Not a tenant route => do nothing
        if (empty($slug)) {
            return $next($request);
        }

        /**
         * DEV FORCE AUTH (safe: local/dev only)
         * If HEADER_FORCE_USER_ID changes (e.g. 85 -> 17), we must SWITCH the Auth user.
         * Otherwise Auth::id() remains the previously logged-in user and your controller will use that.
         */
        $forced = config('header.dev_force_user_id');

        if (app()->environment(['local', 'development']) && is_numeric($forced)) {
            $forcedId = (int) $forced;

            // If not logged in OR logged in as a different user, switch to forced user
            if (!Auth::check() || (int) Auth::id() !== $forcedId) {
                // loginUsingId avoids needing password/phone etc.
                Auth::loginUsingId($forcedId);

                // Optional but recommended: rotate session id after switching user
                $request->session()->migrate(true);
            }
        }

        // Resolve company by slug
        $company = Company::where('slug', $slug)->first();
        if (!$company) {
            abort(404, 'Company not found');
        }

        // Store active company slug (used for redirects)
        session(['active_company_slug' => $company->slug]);

        // Keep your existing company access check unchanged
        if (auth()->check() && auth()->user()->company_id != $company->id) {
            abort(403, 'Unauthorized company access');
        }

        return $next($request);
    }
}
