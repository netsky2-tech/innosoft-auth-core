<?php

use Illuminate\Support\Facades\Route;
use InnoSoft\AuthCore\UI\Http\Controllers\AuthController;
use InnoSoft\AuthCore\UI\Http\Controllers\UserController;
use InnoSoft\AuthCore\UI\Http\Controllers\TeamController;

/*
|--------------------------------------------------------------------------
| API Routes - Auth Core
|--------------------------------------------------------------------------
|
| Version: V1
| Context: Identity & Access Management
|
*/

Route::prefix('api/v1')->name('api.v1.')->group(function () {

    // ========================================================================
    // 🔓 PUBLIC AUTHENTICATION ROUTES (Guest)
    // ========================================================================
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::middleware('throttle:auth-core.login')->group(function () {
            Route::post('login', [AuthController::class, 'login'])->name('login');
            Route::post('register', [AuthController::class, 'register'])->name('register');

            // password recovery
            Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
            Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');

            // 2FA verification
            Route::post('two-factor/verify', [AuthController::class, 'verifyTwoFactor'])->name('verify-two-factor');
        });
    });

    // ========================================================================
    // 🛡️ PROTECTED ROUTES (Requires Bearer Token)
    // ========================================================================

    Route::middleware(['auth:sanctum', 'auth.context'])->group(function () {

        // Logout
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // 2FA Management (User Context)
        Route::prefix('auth/two-factor')->name('auth.two-factor.')->group(function () {
            Route::post('enable', [AuthController::class, 'enableTwoFactor'])->name('enable');
            Route::post('confirm', [AuthController::class, 'confirmTwoFactor'])->name('confirm');
            Route::delete('disable', [AuthController::class, 'disableTwoFactor'])->name('disable');
        });

        // Device / Session Management
        Route::prefix('auth/sessions')->name('auth.sessions.')->group(function () {
            Route::get('/', [AuthController::class, 'getSessions'])->name('index');
            Route::delete('/other', [AuthController::class, 'revokeOtherSessions'])->name('revoke-others');
            Route::delete('/{sessionId}', [AuthController::class, 'revokeSession'])->name('revoke');
        });

        // Teams Management
        Route::prefix('teams')->name('teams.')->group(function () {
            Route::get('/', [TeamController::class, 'index'])->name('index');
            Route::post('/{id}/switch', [TeamController::class, 'switch'])->name('switch');
        });

        // --- User Management (Admin/Self) ---
        Route::apiResource('users', UserController::class);
    });

});