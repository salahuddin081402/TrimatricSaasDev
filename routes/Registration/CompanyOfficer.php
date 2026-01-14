<?php
/* routes/Registration/CompanyOfficer.php */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Registration\CompanyOfficerController;

/*
|--------------------------------------------------------------------------
| Registration » Company Officer
|--------------------------------------------------------------------------
| AppServiceProvider adds:
|   URL prefix  = /registration
|   Name prefix = registration.
|
| Public flow (no changes to header/public dashboard):
| - JS intercepts "Company Officer" clicks, shows key modal, posts to:
|     POST /{company}/company-officer/check-key   -> registration.company_officer.check_key
| - If OK -> redirect to Create page below.
*/

Route::post('/{company}/company-officer/check-key', [CompanyOfficerController::class, 'checkKey'])
    ->name('company_officer.check_key');

Route::get('/{company}/company-officer/create', [CompanyOfficerController::class, 'create'])
    ->name('company_officer.create');
Route::post('/{company}/company-officer', [CompanyOfficerController::class, 'store'])
    ->name('company_officer.store');

Route::get('/{company}/company-officer/edit', [CompanyOfficerController::class, 'edit'])
    ->name('company_officer.edit');
Route::put('/{company}/company-officer', [CompanyOfficerController::class, 'update'])
    ->name('company_officer.update');

/* Dependent selects (AJAX) */
Route::get('/{company}/company-officer/api/geo/districts', [CompanyOfficerController::class, 'districtsByDivision'])
    ->name('company_officer.api.geo.districts'); // ?division_id=#
Route::get('/{company}/company-officer/api/geo/upazilas', [CompanyOfficerController::class, 'upazilasByDistrict'])
    ->name('company_officer.api.geo.upazilas');  // ?district_id=#
Route::get('/{company}/company-officer/api/geo/thanas', [CompanyOfficerController::class, 'thanasByDistrict'])
    ->name('company_officer.api.geo.thanas');    // ?district_id=#
Route::get(
    '/{company}/company-officer/api/trainings',
    [CompanyOfficerController::class, 'trainingsByCategory']
)->name('registration.company_officer.api.trainings');

/* Temp Upload (AJAX) */
Route::post('/{company}/company-officer/api/temp-upload', [CompanyOfficerController::class, 'tempUpload'])
    ->name('company_officer.api.temp_upload');
