<?php
// routes/Backend/Interior/Requisitions.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\Interior\InteriorRequisitionController;

Route::middleware(['web']) // add 'auth' later
    ->group(function () {

        // Effective URL (with AppServiceProvider folder prefix "backend/interior"):
        //   /backend/interior/{company}/requisitions/...
        Route::prefix('{company}/requisitions')
            ->name('interior.requisitions.')
            ->group(function () {

                // Create new requisition (single blade)
                Route::get('/create', [InteriorRequisitionController::class, 'create'])
                    ->name('create');

                // Store first-time insert (sets status='Submitted' on first insert per your rule)
                Route::post('/', [InteriorRequisitionController::class, 'store'])
                    ->name('store');

                // Edit existing requisition (same blade; hydrated)
                Route::get('/{requisition}/edit', [InteriorRequisitionController::class, 'edit'])
                    ->whereNumber('requisition')
                    ->name('edit');

                // Update existing requisition
                Route::match(['PUT', 'POST'], '/{requisition}', [InteriorRequisitionController::class, 'update'])
                    ->whereNumber('requisition')
                    ->name('update');

                // Optional: silent autosave-per-layer (recommended)
                // Route::post('/{requisition}/autosave/step1', [InteriorRequisitionController::class, 'autosaveStep1'])
                //     ->whereNumber('requisition')
                //     ->name('autosave.step1');
                //
                // Route::post('/{requisition}/autosave/state', [InteriorRequisitionController::class, 'autosaveState'])
                //     ->whereNumber('requisition')
                //     ->name('autosave.state');
            });
    });
