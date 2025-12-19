<?php

namespace InnoSoft\AuthCore\UI\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use InnoSoft\AuthCore\Domain\Shared\DomainException;
use Throwable;
trait HandlesAuthErrors
{
    /**
     * Ejecuta una acción de forma segura capturando excepciones del dominio Auth.
     */
    protected function safeExecute(callable $action, string $successMessage = '', int $successStatus = 200): JsonResponse
    {
        try {
            $data = $action();

            // Si la acción ya devolvió una JsonResponse, la retornamos tal cual
            if ($data instanceof JsonResponse) {
                return $data;
            }

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'data'    => $data,
            ], $successStatus);

        } catch (ValidationException $e) {
            return response()->json([
                'success'    => false,
                'error_code' => 'VALIDATION_ERROR',
                'message'    => 'Invalid data',
                'errors'     => $e->errors(),
            ], 422);

        } catch (AuthenticationException $e) {
            return response()->json([
                'success'    => false,
                'error_code' => 'UNAUTHENTICATED',
                'message'    => 'Unauthenticated.',
            ], 401);

        } catch (DomainException $e) {
            // Captura excepciones de negocio (ej. InvalidCredentials, TwoFactorRequired)
            return response()->json([
                'success'    => false,
                'error_code' => $e->getErrorCode() ?? 'DOMAIN_ERROR', // Asumiendo que agregas getErrorCode a tu DomainException
                'message'    => $e->getMessage(),
            ], 400);

        } catch (Throwable $e) {
            // Loguear el error real para los desarrolladores
            report($e);

            return response()->json([
                'success'    => false,
                'error_code' => 'INTERNAL_SERVER_ERROR',
                'message'    => app()->environment('production') ? 'Server Error' : $e->getMessage(),
            ], 500);
        }
    }
}