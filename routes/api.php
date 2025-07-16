<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\UserDetailsController;
use App\Http\Controllers\UserEducationsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\RegisterController;

/* Public (unauthenticated) */
Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);

Route::get('oauth/{provider}',      [OAuthController::class, 'redirect']);
Route::get('oauth/{provider}/back', [OAuthController::class, 'callback']);

/* Protected (token) */
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/details', [UserDetailsController::class, 'store']);
    Route::post('/educations', [UserEducationsController::class, 'store']);

    // Route::middleware('role:national-admin')->get('/admin/metrics', function () {
    //     return ['status' => 'top secret'];
    // });

    Route::post('/logout', [LoginController::class, 'logout']);
});
