<?php

use Illuminate\Support\Facades\Route;
use InnoSoft\AuthCore\UI\Http\Controllers\AuthController;
use InnoSoft\AuthCore\UI\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes - Auth Core
|--------------------------------------------------------------------------
|
| Version: V1
| Context: Identity & Access Management
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

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

    Route::middleware('auth:sanctum')->group(function () {

        // 2FA Management (User Context)
        Route::prefix('auth/two-factor')->name('auth.two-factor.')->group(function () {
            Route::post('enable', [AuthController::class, 'enableTwoFactor'])->name('enable');
            Route::post('confirm', [AuthController::class, 'confirmTwoFactor'])->name('confirm');
            Route::delete('disable', [AuthController::class, 'disableTwoFactor'])->name('disable');
        });

        // --- User Management (Admin/Self) ---
        Route::apiResource('users', UserController::class);
    });

});

/* protect specific endpoint by permission
Route::delete('/users/{id}', [UserController::class, 'destroy'])
    ->middleware('permission:users.delete');*/