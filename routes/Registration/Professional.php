<?php
/**
 * File: routes/registration/Professional.php
 * Final names produced by AppServiceProvider:
 *  - registration.professional.create
 *  - registration.professional.store
 *  - registration.professional.edit
 *  - registration.professional.update
 *  - registration.professional.api.temp_upload
 *  - registration.professional.api.geo.districts
 *  - registration.professional.api.geo.upazilas
 *  - registration.professional.api.geo.thanas
 *  - registration.professional.api.trainings
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Registration\ProfessionalController;

// Public flow (no reg_key needed)
Route::get('/{company}/professional/create', [ProfessionalController::class, 'create'])
    ->name('professional.create');

Route::post('/{company}/professional', [ProfessionalController::class, 'store'])
    ->name('professional.store');

// Edit/Update
Route::get('/{company}/professional/edit', [ProfessionalController::class, 'edit'])
    ->name('professional.edit');

Route::put('/{company}/professional', [ProfessionalController::class, 'update'])
    ->name('professional.update');

// Dependent AJAX (parity with Company Officer)
Route::get('/{company}/professional/api/geo/districts', [ProfessionalController::class, 'districtsByDivision'])
    ->name('professional.api.geo.districts');

Route::get('/{company}/professional/api/geo/upazilas', [ProfessionalController::class, 'upazilasByDistrict'])
    ->name('professional.api.geo.upazilas');

Route::get('/{company}/professional/api/geo/thanas', [ProfessionalController::class, 'thanasByDistrict'])
    ->name('professional.api.geo.thanas');

// Trainings (if reused)
Route::get('/{company}/professional/api/trainings', [ProfessionalController::class, 'trainingsByCategory'])
    ->name('professional.api.trainings');

// Temp upload for images
Route::post('/{company}/professional/api/temp-upload', [ProfessionalController::class, 'tempUpload'])
    ->name('professional.api.temp_upload');
