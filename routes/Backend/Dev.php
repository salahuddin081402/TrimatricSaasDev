<?php
/* routes/Backend/Dev.php */
use Illuminate\Support\Facades\Route;

// Global (non-tenant) dev page
Route::get('dev/jurisdiction-demo', function () {
    return view('backend.dev.jurisdiction-test');
})->name('dev.jurisdiction-demo');

// Tenant-aware dev page (company slug)
Route::get('company/{company:slug}/dev/jurisdiction-demo', function () {
    return view('backend.dev.jurisdiction-test');
})->name('company.dev.jurisdiction-demo');
