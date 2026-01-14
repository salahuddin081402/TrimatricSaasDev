<?php
/* routes/Backend/Geo.php */
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\GeoController;

Route::prefix('ajax/geo')->name('ajax.geo.')->group(function () {
    Route::get('divisions', [GeoController::class, 'divisions'])->name('divisions');
    Route::get('districts', [GeoController::class, 'districts'])->name('districts');
    Route::get('clusters',  [GeoController::class, 'clusters'])->name('clusters');
});
