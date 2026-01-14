<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', fn() => response('ok', 200));
Route::get('/dbcheck', fn() => DB::select('select 1 as ok'));


//Auth Routes
Route::prefix('backend/company/{company}')
    ->middleware(['company'])
    ->as('company.')
    ->group(function () {

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
    Route::post('/signup', [AuthController::class, 'signup']);

    Route::get('/otp', [AuthController::class, 'showOtp'])->name('otp');
    Route::post('/otp', [AuthController::class, 'verifyOtp']);
    Route::post('/otp/resend', [AuthController::class, 'resendOtp'])->name('otp.resend');

    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('forgotPassword');
    Route::post('/forgot-password', [AuthController::class, 'sendForgotOtp'])->name('forgotPassword.submit');

    Route::get('/reset-password', [AuthController::class, 'showReset'])->name('resetPassword');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('resetPassword.submit');

    // Route::get('/dashboard/public', function () {
    //     return view('dashboard');
    // })->middleware('auth');
});

//Goole OAuth Routes
Route::get('/backend/company/{company}/auth/google', [AuthController::class, 'googleRedirect'])
    ->name('login.google');
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback'])
    ->name('login.google.callback');

//Facebook OAuth Routes
Route::get('/backend/company/{company}/auth/facebook', [AuthController::class, 'facebookRedirect'])
    ->name('login.facebook');
Route::get('/auth/facebook/callback', [AuthController::class, 'facebookCallback'])
    ->name('login.facebook.callback');

//Logout Route
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');
  //  ->middleware('auth');

