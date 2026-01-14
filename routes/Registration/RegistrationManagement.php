<?php
/** TMX-RMGM | routes/registration/RegistrationManagement.php | v3.2 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Registration\RegistrationManagementController as C;

/* Page */
Route::get('/{company}/manage', [C::class, 'index'])->name('mgmt.index');

/* Data API */
Route::get('/{company}/manage/data',           [C::class, 'data'])->name('mgmt.data');
Route::get('/{company}/manage/roles',          [C::class, 'roles'])->name('mgmt.roles');
Route::get('/{company}/manage/geo/divisions',  [C::class, 'geoDivisions'])->name('mgmt.geo.divisions');
Route::get('/{company}/manage/geo/districts',  [C::class, 'geoDistricts'])->name('mgmt.geo.districts');
Route::get('/{company}/manage/geo/clusters',   [C::class, 'geoClusters'])->name('mgmt.geo.clusters');

/* Actions */
Route::post('/{company}/manage/{id}/approve',     [C::class, 'approve'])->name('mgmt.approve');
Route::post('/{company}/manage/{id}/decline',     [C::class, 'decline'])->name('mgmt.decline');
Route::post('/{company}/manage/{id}/terminate',   [C::class, 'terminate'])->name('mgmt.terminate');

Route::post('/{company}/manage/{id}/role-assign', [C::class, 'roleAssign'])->name('mgmt.role.assign');
Route::post('/{company}/manage/{id}/role-deassign',[C::class, 'roleDeassign'])->name('mgmt.role.deassign'); // <-- needed

/* Modal info + core de-assign */
Route::get('/{company}/manage/{regId}/modal-info',     [C::class, 'modalInfo'])->name('mgmt.modal.info');
Route::post('/{company}/manage/{regId}/deassign-core', [C::class, 'deassignCore'])->name('mgmt.role.deassign.core');

/* Scoped de-assign (not used by Blade directly, kept for compatibility) */
Route::post('/{company}/manage/deassign/division',       [C::class, 'deassignDivision'])->name('mgmt.deassign.division');
Route::post('/{company}/manage/deassign/district',       [C::class, 'deassignDistrict'])->name('mgmt.deassign.district');
Route::post('/{company}/manage/deassign/cluster-admin',  [C::class, 'deassignClusterAdmin'])->name('mgmt.deassign.cluster_admin');
Route::post('/{company}/manage/deassign/cluster-member', [C::class, 'deassignClusterMember'])->name('mgmt.deassign.cluster_member');

/* PDF */
Route::get('/{company}/manage/{id}/pdf', [C::class, 'pdf'])->name('mgmt.pdf');

/* Export */
Route::get('/{company}/manage/export/csv', [C::class, 'exportCsv'])->name('mgmt.export.csv');

/* Distinct values */
Route::get('/{company}/manage/distinct', [C::class, 'distinctValues'])->name('mgmt.distinct');

/* Promotion */
Route::post('/{company}/manage/{regId}/promote', [C::class, 'promote'])->name('mgmt.promote');
