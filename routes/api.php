<?php

use App\Http\Controllers\CpdController;
use App\Http\Controllers\SupportStaffController;
use App\Http\Controllers\TicketController;
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
            Route::post('/members/confirm', [MembershipController::class, 'confirm']);
        });

        /** Member Only routes */
        Route::middleware('role:member')->group( function () {
            Route::post('/tickets', [TicketController::class, 'store']);
            Route::get('/tickets/mine', [TicketController::class, 'mine']);
        });

        /** Support Staff Only Routes */
        Route::middleware('role:support-staff')->group(function () {
            Route::get('tickets/support', [TicketController::class, 'support']);
        });

        /** State Admin Only Routes */
        Route::middleware('role:state-admin')->group(function () {
            Route::get('/cpd/logs/state', [CpdController::class, 'state']);
            Route::get('tickets/state', [TicketController::class, 'state']);
            Route::get('/supports/state', [SupportStaffController::class, 'state']);
        });

        /** National Admin Only Routes */
        Route::middleware('role:national-admin')->group( function () {
            Route::get('/cpd/logs', [CpdController::class, 'index']);
            Route::post('/register/admin', [RegisterController::class, 'registerAdmin']);
            Route::put('/members/approve/{id}', [MembershipController::class, 'approve']);
            Route::put('/members/suspend/{id}', [MembershipController::class, 'suspend']);
            Route::get('/members', [MembershipController::class, 'index']);
            Route::get('/supports', [SupportStaffController::class, 'index']);
            Route::get('/tickets', [TicketController::class, 'index']);
        });

        /** National-Admin & State-Admin Only Routes */
        Route::middleware('role:state-admin|national-admin')->group(function() {
            Route::post('/cpd/activities', [CpdController::class, 'store']);
            Route::get('/members/stats', [MembershipController::class, 'stats']);
            Route::get('/members/state', [MembershipController::class, 'state']);
            Route::get('/members/{id}', [MembershipController::class, 'view']);
            Route::delete('/members/{id}', [MembershipController::class, 'delete']);
            Route::post('/tickets/assign', [TicketController::class, 'assign']);
        });

        /** Private users route */
        Route::middleware('role:member|support-staff|state-admin|national-admin')->group( function () {
            Route::get('/tickets/{id}', [TicketController::class, 'view']);
        });
    });
});

Route::post('/logout', [LoginController::class, 'logout']);
