<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\Communication\BatchDeliveryController;

Route::middleware(['web']) // add 'auth' later when ready
    ->group(function () {

        // Effective URL (with AppServiceProvider folder prefix "backend/communication"):
        //   /backend/communication/{company}/batch-delivery/...
        Route::prefix('{company}/batch-delivery')
            ->name('batch_delivery.')
            ->group(function () {

                // Single Blade screen (left menu + right panel)
                Route::get('/', [BatchDeliveryController::class, 'index'])
                    ->name('index');

                /*
                 |------------------------------------------------------------------
                 | Geo APIs (Phase-1) - for TomSelect cascade
                 |------------------------------------------------------------------
                 | Division -> District -> Cluster
                 | Each must support "ALL" in UI (UI adds ALL option; API returns real rows).
                 */
                Route::get('/api/geo/divisions', [BatchDeliveryController::class, 'geoDivisions'])
                    ->name('api.geo.divisions');

                Route::get('/api/geo/districts', [BatchDeliveryController::class, 'geoDistricts'])
                    ->name('api.geo.districts'); // expects ?division_id=ID (or empty)

                Route::get('/api/geo/clusters', [BatchDeliveryController::class, 'geoClusters'])
                    ->name('api.geo.clusters'); // expects ?district_id=ID (or empty)

                /*
                 |------------------------------------------------------------------
                 | Client & Entrepreneur (Phase-1)
                 |------------------------------------------------------------------
                 */
                Route::post('/client-entrepreneur/estimate', [BatchDeliveryController::class, 'estimateClientEntrepreneur'])
                    ->name('client_entrepreneur.estimate');

                Route::post('/client-entrepreneur/dispatch', [BatchDeliveryController::class, 'dispatchClientEntrepreneur'])
                    ->name('client_entrepreneur.dispatch');

                Route::get('/batch/{batch_no}/status', [BatchDeliveryController::class, 'batchStatus'])
                    ->name('batch.status');

                Route::post('/batch/{batch_no}/cancel', [BatchDeliveryController::class, 'cancelBatch'])
                    ->name('batch.cancel');

                /*
                 |------------------------------------------------------------------
                 | History (Phase-1)
                 |------------------------------------------------------------------
                 */
                Route::get('/history', [BatchDeliveryController::class, 'history'])
                    ->name('history');

                Route::get('/history/data', [BatchDeliveryController::class, 'historyData'])
                    ->name('history.data');

                Route::get('/history/{batch_no}/details', [BatchDeliveryController::class, 'historyDetails'])
                    ->name('history.details');
            });
    });
