<?php
/* routes/Backend/Dashboard.php */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\DashboardController;

/*
|----------------------------------------------------------------------
| Dashboard routes
|----------------------------------------------------------------------
| Default global dashboards
|   /dashboard/public  → public landing
|   /dashboard         → role-aware dashboard
|
| Tenant-specific dashboards (with company slug in URL)
|   /company/{company:slug}/dashboard/public
|   /company/{company:slug}/dashboard
|   → these allow logo/company context to show automatically
*/

/* -------- Global (non-tenant specific) -------- */
Route::get('/dashboard/public', [DashboardController::class, 'public'])
    ->name('dashboard.public');

Route::get('/dashboard', [DashboardController::class, 'index'])
    // ->middleware('auth') // enable after Breeze/Auth
    ->name('dashboard.index');

/* -------- Tenant-aware -------- */
Route::prefix('company/{company:slug}/dashboard')->name('company.dashboard.')->group(function () {
    Route::get('public', [DashboardController::class, 'public'])
        ->name('public');

    Route::get('/', [DashboardController::class, 'index'])
        ->name('index');
});
