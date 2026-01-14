<?php
/* routes\SuperAdmin\GlobalSetup\GlobalSetup.php  */
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\GlobalSetup\CountryController;
use App\Http\Controllers\SuperAdmin\GlobalSetup\CompanyController;
use App\Http\Controllers\SuperAdmin\GlobalSetup\ClusterController;

/*
|--------------------------------------------------------------------------
| Super Admin » Global Setup
|--------------------------------------------------------------------------
| Thanks to App\Providers\AppServiceProvider::boot(), this file is loaded
| with:
|   URL prefix  = /superadmin/globalsetup
|   Name prefix = superadmin.globalsetup.
|
| So the resource below:
|   Route::resource('companies', CompanyController::class);
| becomes routes like:
|   GET  /superadmin/globalsetup/companies         -> superadmin.globalsetup.companies.index
|   GET  /superadmin/globalsetup/companies/create  -> superadmin.globalsetup.companies.create
|   POST /superadmin/globalsetup/companies         -> superadmin.globalsetup.companies.store
|   GET  /superadmin/globalsetup/companies/{id}    -> superadmin.globalsetup.companies.show
|   GET  /superadmin/globalsetup/companies/{id}/edit -> superadmin.globalsetup.companies.edit
|   PUT  /superadmin/globalsetup/companies/{id}    -> superadmin.globalsetup.companies.update
|   DELETE /superadmin/globalsetup/companies/{id}  -> superadmin.globalsetup.companies.destroy
|
| Same idea for countries (already in use).
*/

Route::resource('countries', CountryController::class);
Route::resource('companies', CompanyController::class);
Route::resource('clusters', ClusterController::class);
