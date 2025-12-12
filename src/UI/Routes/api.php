<?php
use Illuminate\Support\Facades\Route;
use InnoSoft\AuthCore\UI\Http\Controllers\AuthController;

Route::prefix('api/auth')->middleware('api')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
});

Route::prefix('api/auth')->middleware('throttle:auth-core.login')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});