<?php
/* routes/Registration/Client.php */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Registration\ClientRegistrationController;

/*
|--------------------------------------------------------------------------
| Registration » Client
|--------------------------------------------------------------------------
| AppServiceProvider adds:
|   URL prefix  = /registration
|   Name prefix = registration.
| So the route:
|   Route::get('/{company}/client/create', ...) -> registration.client.create
*/

Route::get('/{company}/client/create', [ClientRegistrationController::class, 'create'])
    ->name('client.create');
Route::post('/{company}/client', [ClientRegistrationController::class, 'store'])
    ->name('client.store');

Route::get('/{company}/client/edit', [ClientRegistrationController::class, 'edit'])
    ->name('client.edit');
Route::put('/{company}/client', [ClientRegistrationController::class, 'update'])
    ->name('client.update');

/* Dependent selects (AJAX) */
Route::get('/{company}/api/geo/districts', [ClientRegistrationController::class, 'districtsByDivision'])
    ->name('api.geo.districts'); // ?division_id=#
Route::get('/{company}/api/geo/upazilas', [ClientRegistrationController::class, 'upazilasByDistrict'])
    ->name('api.geo.upazilas');  // ?district_id=#
Route::get('/{company}/api/geo/thanas', [ClientRegistrationController::class, 'thanasByDistrict'])
    ->name('api.geo.thanas');    // ?district_id=#   // NEW
