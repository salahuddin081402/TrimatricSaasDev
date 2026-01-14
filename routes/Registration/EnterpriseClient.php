<?php
/**
 * File: routes/registration/EnterpriseClient.php
 * Final names produced by AppServiceProvider (examples):
 *  - registration.enterprise_client.step1.create
 *  - registration.enterprise_client.step1.store
 *  - registration.enterprise_client.step2.create
 *  - registration.enterprise_client.step2.store
 *  - registration.enterprise_client.api.temp_upload
 *  - registration.enterprise_client.api.geo.districts
 *  - registration.enterprise_client.api.geo.upazilas
 *  - registration.enterprise_client.api.geo.thanas
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Registration\EnterpriseClientController;

/* ===================== CREATE FLOW: 2 STEPS ===================== */
// Step-1
Route::get('/{company}/enterprise-client/step-1/create', [EnterpriseClientController::class, 'step1Create'])
    ->name('enterprise_client.step1.create');
Route::post('/{company}/enterprise-client/step-1', [EnterpriseClientController::class, 'step1Store'])
    ->name('enterprise_client.step1.store');

// Step-2 (FINAL STEP)
Route::get('/{company}/enterprise-client/step-2/create', [EnterpriseClientController::class, 'step2Create'])
    ->name('enterprise_client.step2.create');
Route::post('/{company}/enterprise-client/step-2', [EnterpriseClientController::class, 'step2Store'])
    ->name('enterprise_client.step2.store');

/* ===================== EDIT/UPDATE PLACEHOLDERS ===================== */
// Optional: keep for future “edit per step” or summary edit.
Route::get('/{company}/enterprise-client/edit', [EnterpriseClientController::class, 'edit'])
    ->name('enterprise_client.edit');
Route::put('/{company}/enterprise-client', [EnterpriseClientController::class, 'update'])
    ->name('enterprise_client.update');

// Entry point from public dashboard/header for editing an existing registration.
// Expects `company` route param and `user_id` via query string (or resolved in controller).
Route::get('/{company}/enterprise-client/edit-entry', [EnterpriseClientController::class, 'editEntry'])
    ->name('enterprise_client.edit_entry.go');

/* ===================== DEPENDENT AJAX (GEO) ===================== */
Route::get('/{company}/enterprise-client/api/geo/districts', [EnterpriseClientController::class, 'districtsByDivision'])
    ->name('enterprise_client.api.geo.districts');
Route::get('/{company}/enterprise-client/api/geo/upazilas', [EnterpriseClientController::class, 'upazilasByDistrict'])
    ->name('enterprise_client.api.geo.upazilas');
Route::get('/{company}/enterprise-client/api/geo/thanas', [EnterpriseClientController::class, 'thanasByDistrict'])
    ->name('enterprise_client.api.geo.thanas');

/* ===================== TEMP UPLOAD ===================== */
Route::post('/{company}/enterprise-client/api/temp-upload', [EnterpriseClientController::class, 'tempUpload'])
    ->name('enterprise_client.api.temp_upload');
