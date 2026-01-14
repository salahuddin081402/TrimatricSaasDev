<?php
/**
 * File: routes/registration/Entrepreneur.php
 * Final names produced by AppServiceProvider (examples):
 *  - registration.entrepreneur.step1.create
 *  - registration.entrepreneur.step1.store
 *  - ...
 *  - registration.entrepreneur.step6.create
 *  - registration.entrepreneur.step6.store
 *  - registration.entrepreneur.api.temp_upload
 *  - registration.entrepreneur.api.geo.districts
 *  - registration.entrepreneur.api.geo.upazilas
 *  - registration.entrepreneur.api.geo.thanas
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Registration\EntrepreneurController;

/* ===================== CREATE FLOW: 6 STEPS ===================== */
// Step-1
Route::get('/{company}/entrepreneur/step-1/create', [EntrepreneurController::class, 'step1Create'])
    ->name('entrepreneur.step1.create');
Route::post('/{company}/entrepreneur/step-1', [EntrepreneurController::class, 'step1Store'])
    ->name('entrepreneur.step1.store');

// Step-2
Route::get('/{company}/entrepreneur/step-2/create', [EntrepreneurController::class, 'step2Create'])
    ->name('entrepreneur.step2.create');
Route::post('/{company}/entrepreneur/step-2', [EntrepreneurController::class, 'step2Store'])
    ->name('entrepreneur.step2.store');

// Step-3
Route::get('/{company}/entrepreneur/step-3/create', [EntrepreneurController::class, 'step3Create'])
    ->name('entrepreneur.step3.create');
Route::post('/{company}/entrepreneur/step-3', [EntrepreneurController::class, 'step3Store'])
    ->name('entrepreneur.step3.store');

// Step-4
Route::get('/{company}/entrepreneur/step-4/create', [EntrepreneurController::class, 'step4Create'])
    ->name('entrepreneur.step4.create');
Route::post('/{company}/entrepreneur/step-4', [EntrepreneurController::class, 'step4Store'])
    ->name('entrepreneur.step4.store');

// Step-5
Route::get('/{company}/entrepreneur/step-5/create', [EntrepreneurController::class, 'step5Create'])
    ->name('entrepreneur.step5.create');
Route::post('/{company}/entrepreneur/step-5', [EntrepreneurController::class, 'step5Store'])
    ->name('entrepreneur.step5.store');

// Step-6
Route::get('/{company}/entrepreneur/step-6/create', [EntrepreneurController::class, 'step6Create'])
    ->name('entrepreneur.step6.create');
Route::post('/{company}/entrepreneur/step-6', [EntrepreneurController::class, 'step6Store'])
    ->name('entrepreneur.step6.store');

/* ===================== EDIT/UPDATE PLACEHOLDERS ===================== */
// Optional: keep for future “edit per step” or summary edit.
Route::get('/{company}/entrepreneur/edit', [EntrepreneurController::class, 'edit'])
    ->name('entrepreneur.edit');
Route::put('/{company}/entrepreneur', [EntrepreneurController::class, 'update'])
    ->name('entrepreneur.update');

/* Dedicated entrypoint for post-registration edit flow (6-step wizard clone) */
Route::get('/{company}/entrepreneur/edit-entry', [EntrepreneurController::class, 'editEntry'])
    ->name('entrepreneur.edit_entry.go');

/* ===================== DEPENDENT AJAX (GEO) ===================== */
Route::get('/{company}/entrepreneur/api/geo/districts', [EntrepreneurController::class, 'districtsByDivision'])
    ->name('entrepreneur.api.geo.districts');
Route::get('/{company}/entrepreneur/api/geo/upazilas', [EntrepreneurController::class, 'upazilasByDistrict'])
    ->name('entrepreneur.api.geo.upazilas');
Route::get('/{company}/entrepreneur/api/geo/thanas', [EntrepreneurController::class, 'thanasByDistrict'])
    ->name('entrepreneur.api.geo.thanas');

/* ===================== TEMP UPLOAD ===================== */
Route::post('/{company}/entrepreneur/api/temp-upload', [EntrepreneurController::class, 'tempUpload'])
    ->name('entrepreneur.api.temp_upload');

/* ===================== OPTIONAL LOOKUPS (LAZY TOMSELECT) ===================== */
/* Step-6: Trainings by Category (for dependent TomSelect) */
Route::get('/{company}/entrepreneur/api/trainings', [EntrepreneurController::class, 'trainingsByCategory'])
    ->name('entrepreneur.api.trainings'); // ?category_id=INT (required)

/* Step-3: Degrees (if you don’t server-render full list) */
Route::get('/{company}/entrepreneur/api/lookup/degrees', [EntrepreneurController::class, 'degrees'])
    ->name('entrepreneur.api.degrees'); // ?q=STRING (optional)

/* Step-4: Software list (status=1) */
Route::get('/{company}/entrepreneur/api/lookup/software', [EntrepreneurController::class, 'software'])
    ->name('entrepreneur.api.software'); // ?q=STRING (optional)

/* Step-4: Skills list (status=1) */
Route::get('/{company}/entrepreneur/api/lookup/skills', [EntrepreneurController::class, 'skills'])
    ->name('entrepreneur.api.skills'); // ?q=STRING (optional)

/* Step-5: Tasks/Preferred Areas (status=1) */
Route::get('/{company}/entrepreneur/api/lookup/tasks', [EntrepreneurController::class, 'tasks'])
    ->name('entrepreneur.api.tasks'); // ?q=STRING (optional)
