<?php

use App\Http\Controllers\ActionController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CpdController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\StatisticsController;
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
        /** Public users only route */
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
            Route::get('/cpd/logs/mine', [CpdController::class, 'member']);
            Route::post('/cpd/logs', [CpdController::class, 'log']);
            Route::get('/cpd/logs/stats', [CpdController::class, 'stats']);
            Route::post('/tickets', [TicketController::class, 'store']);
            Route::get('/tickets/mine', [TicketController::class, 'mine']);
        });

        /** Support Staff Only Routes */
        Route::middleware('role:support-staff')->group(function () {
            Route::get('/tickets/support', [TicketController::class, 'support']);
            Route::post('/tickets/close/{id}', [TicketController::class, 'close']);
        });

        /** Case Manager Only Routes */
        Route::middleware('role:case-manager')->group(function () {
            Route::get('/members/cases', [MembershipController::class, 'cases']);
            Route::post('/members/cases/review/{id}', [MembershipController::class, 'review']);
            Route::post('/members/approval/request/{id}', [MembershipController::class, 'requestApproval']);
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
            Route::get('/stats/national', [StatisticsController::class, 'national']);
            Route::get('charts/national', [StatisticsController::class, 'national_charts']);
            Route::get('report/national', [StatisticsController::class, 'national_breakdown']);
        });

        /** National-Admin & State-Admin Only Routes */
        Route::middleware('role:state-admin|national-admin')->group(function() {
            Route::get('/actions/activities', [ActionController::class, 'recentActivities']);
            Route::get('/actions/audits', [ActionController::class, 'auditLogs']);
            Route::post('/cpd/activities', [CpdController::class, 'store']);
            Route::get('/members/stats', [StatisticsController::class, 'members_count']);
            Route::get('/members/state', [MembershipController::class, 'state']);
            Route::get('/members/{id}', [MembershipController::class, 'view']);
            Route::delete('/members/{id}', [MembershipController::class, 'delete']);
            Route::post('/tickets/assign', [TicketController::class, 'assign']);
            Route::post('/cpd/logs/approve/{id}', [CpdController::class, 'approve']);
            Route::post('/cpd/logs/reject/{id}', [CpdController::class, 'reject']);
            Route::post('/cpd/activities/all', [CpdController::class, 'activities']);
        });

        /** Some private users route */
        Route::middleware('role:member|support-staff|state-admin|national-admin')->group( function () {
            Route::get('/tickets/{id}', [TicketController::class, 'view']);
            Route::get('/cpd/activities', [CpdController::class, 'current']);
        });

        /** All private users route */
        Route::middleware('role:member|support-staff|state-admin|national-admin|case-manager')->group( function () {
            Route::get('/chats', [ChatController::class, 'index']);
            Route::post('/chats', [ChatController::class, 'store']);
            Route::get('/chats/{chat}', [ChatController::class, 'show']);
            Route::get('/chats/users/available', [ChatController::class, 'getAvailableUsers']);

            Route::post('/chats/{chat}/messages', [MessageController::class, 'store']);
            Route::post('/chats/{chat}/messages/read', [MessageController::class, 'markAsRead']);
            Route::post('/chats/{chat}/typing', [MessageController::class, 'typing']);
            Route::get('/messages/{message}/attachments/{index}', [MessageController::class, 'downloadAttachment']);
        });
    });
});

Route::post('/logout', [LoginController::class, 'logout']);
