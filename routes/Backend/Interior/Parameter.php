<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Backend\Interior\InteriorParameterController;

Route::middleware(['web']) // add 'auth' later when ready
    ->group(function () {

        // Effective URL (with AppServiceProvider folder prefix "backend/interior"):
        //   /backend/interior/{company}/parameters/...
        Route::prefix('{company}/parameters')
            ->name('interior.parameters.')
            ->group(function () {

                // Main screen (left menu + right panel)
                Route::get('/', [InteriorParameterController::class, 'index'])
                    ->name('index');

                /*
                 |------------------------------------------------------------------
                 | Unified endpoints (required for your patched index.blade.php)
                 |------------------------------------------------------------------
                 | Create: POST   /{company}/parameters        (entity from request('_entity'))
                 | Update: PUT    /{company}/parameters/{entity}/{id}
                 | Delete: DELETE /{company}/parameters/{entity}/{id}
                 |
                 | NOTE: whereNumber('id') prevents conflict with /data/*, /create/* etc.
                 |
                 | No app()->call(). Controller is injected properly.
                 */
                Route::post('/', function (
                    Request $request,
                    $company,
                    InteriorParameterController $controller
                ) {
                    $entity = (string) $request->input('_entity', '');
                    abort_if($entity === '', 422, 'Missing _entity');

                    return $controller->store($request, $company, $entity);
                })->name('save');

                Route::match(['PUT', 'POST'], '/{entity}/{id}', function (
                    Request $request,
                    $company,
                    $entity,
                    $id,
                    InteriorParameterController $controller
                ) {
                    return $controller->update($request, $company, $entity, $id);
                })->whereNumber('id')->name('save_update');

                Route::delete('/{entity}/{id}', function (
                    Request $request,
                    $company,
                    $entity,
                    $id,
                    InteriorParameterController $controller
                ) {
                    return $controller->destroy($request, $company, $entity, $id);
                })->whereNumber('id')->name('save_destroy');

                // Existing records list (for right-side search/filter)
                Route::get('/data/{entity}', [InteriorParameterController::class, 'data'])
                    ->name('data');

                // (Legacy/optional) entity-specific endpoints — keep if still used elsewhere
                Route::get('/create/{entity}', [InteriorParameterController::class, 'create'])
                    ->name('create');

                Route::post('/store/{entity}', [InteriorParameterController::class, 'store'])
                    ->name('store');

                Route::get('/edit/{entity}/{id}', [InteriorParameterController::class, 'edit'])
                    ->name('edit');

                Route::match(['POST', 'PUT'], '/update/{entity}/{id}', [InteriorParameterController::class, 'update'])
                    ->name('update');

                Route::delete('/delete/{entity}/{id}', [InteriorParameterController::class, 'destroy'])
                    ->name('destroy');

                // Standalone image upload (AJAX)
                Route::post('/upload-image/{entity}', [InteriorParameterController::class, 'uploadImage'])
                    ->name('upload_image');
            });
    });
