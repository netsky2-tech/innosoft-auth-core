<?php

use Illuminate\Support\Facades\Route;
use InnoSoft\AuthCore\UI\Http\Controllers\AuthController;
use InnoSoft\AuthCore\UI\Http\Controllers\PermissionController;
use InnoSoft\AuthCore\UI\Http\Controllers\RoleController;
use InnoSoft\AuthCore\UI\Http\Controllers\UserController;
use InnoSoft\AuthCore\UI\Http\Controllers\TeamController;
use InnoSoft\AuthCore\UI\Http\Controllers\AuditController;

/*
|--------------------------------------------------------------------------
| API Routes - Auth Core (V1)
|--------------------------------------------------------------------------
|
| Context: Identity & Access Management
|
*/

// El prefijo base se define en el ServiceProvider, aquí solo definimos la estructura interna.
// El ServiceProvider se encargará de envolver esto en: [Config Prefix] + /v1

Route::name('api.v1.')->group(function () {

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

    Route::middleware(['auth:sanctum', 'auth.context', 'throttle:auth-core.api'])->group(function () {

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
        Route::prefix('users')->name('users.')->group(function () {
            Route::post('{id}/roles', [UserController::class, 'assignRole'])->name('roles.assign');
            Route::delete('{id}/roles', [UserController::class, 'revokeRole'])->name('roles.revoke');
        });

        // --- Role Management ---
        Route::apiResource('roles', RoleController::class);
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::post('{name}/permissions/sync', [RoleController::class, 'syncPermissions'])->name('permissions.sync');
            Route::post('{name}/permissions', [RoleController::class, 'givePermission'])->name('permissions.give');
            Route::delete('{name}/permissions', [RoleController::class, 'revokePermission'])->name('permissions.revoke');
        });

        // --- Permission Management ---
        Route::apiResource('permissions', PermissionController::class);

        // --- Audit Logs ---
        Route::prefix('audit')->name('audit.')->group(function () {
            Route::get('logs', [AuditController::class, 'index'])->name('logs.index');
            Route::get('users/{id}/logs', [AuditController::class, 'userLogs'])->name('users.logs');
        });
    });

});
