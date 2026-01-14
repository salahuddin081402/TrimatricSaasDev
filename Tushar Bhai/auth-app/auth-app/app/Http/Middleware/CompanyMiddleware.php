<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Company;

class CompanyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('company');

        if (!$slug) {
            abort(404, 'Company not specified');
        }

        $company = Company::where('slug', $slug)
            ->first();

        if (!$company) {
            abort(404, 'Company not found');
        }

        if (auth()->check() && auth()->user()->company_id != $company->id) {
            abort(403, 'Unauthorized company access');
        }

        return $next($request);
    }
}
