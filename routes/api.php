<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OAuthController;

/* Public (unauthenticated) */
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::get('oauth/{provider}',      [OAuthController::class, 'redirect']);
Route::get('oauth/{provider}/back', [OAuthController::class, 'callback']);

/* Protected (token) */
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', fn (Request $r) => $r->user());

    Route::middleware('role:national-admin')->get('/admin/metrics', function () {
        return ['status' => 'top secret'];
    });

    Route::post('/logout', [AuthController::class, 'logout']);
});
