<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\UserDetailsController;
use App\Http\Controllers\UserDocumentsController;
use App\Http\Controllers\UserEducationsController;
use App\Http\Controllers\UserEmploymentsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\MembershipController;

/* Public (unauthenticated) */
Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);

Route::get('oauth/{provider}',      [OAuthController::class, 'redirect']);
Route::get('oauth/{provider}/back', [OAuthController::class, 'callback']);

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

/* Protected (token) */
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [UserController::class, 'me']);
    Route::middleware(['verified'])->group(function () {
        Route::middleware('role:guest')->group( function () {
            Route::post('/details', [UserDetailsController::class, 'store']);
            Route::post('/educations', [UserEducationsController::class, 'store']);
            Route::post('/employments', [UserEmploymentsController::class, 'store']);
            Route::post('/specialization', [UserDetailsController::class, 'add_specialization']);
            Route::post('/documents', [UserDocumentsController::class, 'store']);
            Route::post('/confirm-membership', [UserDetailsController::class, 'confirmMembership']);
        });

        /** National Admin Only Routes */
        Route::middleware('role:national-admin')->group( function () {
            Route::post('/verify-member', [UserDetailsController::class, 'verifyMember']);
            Route::get('/all-members', [MembershipController::class, 'index']);
        });
    });


    Route::post('/logout', [LoginController::class, 'logout']);
});
