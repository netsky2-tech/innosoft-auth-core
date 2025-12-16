<?php

namespace InnoSoft\AuthCore\Infrastructure\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class AuthCoreExceptionHandler
{
    /**
     * Registra los callbacks de renderizado en la configuración de Laravel.
     */
    public static function register(Exceptions $exceptions): void
    {
        // 1. Handle validations
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'error_code' => 'VALIDATION_ERROR',
                    'message'    => 'Invalid data',
                    'errors'     => $e->errors(),
                ], 422);
            }
        });

        // 2. Handle authentication
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'error_code' => 'UNAUTHENTICATED',
                    'message'    => 'Invalid token.',
                ], 401);
            }
        });

        // 3. Handle resources not found
        $exceptions->render(function (NotFoundHttpException|ModelNotFoundException $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'error_code' => 'RESOURCE_NOT_FOUND',
                    'message'    => 'The requested resource could not be found.',
                ], 404);
            }
        });

        // 4. Handle denied access (403)
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'error_code' => 'ACCESS_DENIED',
                    'message'    => 'You are not allowed to access this resource.',
                ], 403);
            }
        });

        // 5. "Catch All"
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                $isProduction = app()->environment('production');

                return response()->json([
                    'success'    => false,
                    'error_code' => 'INTERNAL_SERVER_ERROR',
                    'message'    => $isProduction ? 'Internal server error.' : $e->getMessage(),
                    'trace'      => $isProduction ? null : $e->getTraceAsString(), // Only for devs
                ], 500);
            }
        });
    }
}