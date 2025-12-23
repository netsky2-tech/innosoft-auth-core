<?php

namespace InnoSoft\AuthCore\UI\Http\Traits;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use InnoSoft\AuthCore\Domain\Shared\DomainException;
use Throwable;
trait HandlesApiExecution
{
    /**
     * Encapsulate the execution of the controller in a standardized try-catch block.
     */
    protected function safeExecute(callable $action, string $message, int $code): JsonResponse
    {
        try {
            $result = $action();

            // Si la acción ya devolvió una JsonResponse, la retornamos tal cual
            if ($result instanceof JsonResponse) {
                return $result;
            }

            return $this->successResponse($result, $message, $code);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, 'VALIDATION_ERROR', $e->errors());

        } catch (AuthenticationException $e) {
            return $this->errorResponse('Unauthenticated.', 401, 'UNAUTHENTICATED');

        } catch (ModelNotFoundException $e) {
            // Captura errores nativos de Eloquent si se escapan de los handlers
            return $this->errorResponse('Resource not found.', 404, 'NOT_FOUND');

        } catch (DomainException $e) {
            // Captura excepciones de negocio (ej. InvalidCredentials, TwoFactorRequired)
            $code = $e->getCode();
            $httpCode = ($code >= 100 && $code <= 599) ? $code : 400;

            return $this->errorResponse(
                $e->getMessage(),
                $httpCode,
                $e->getErrorCode() ?? 'DOMAIN_ERROR'
            );

        } catch (Throwable $e) {
            // Loguear el error real para los desarrolladores
            report($e);

            // Respuesta segura para el cliente
            $message = app()->environment('production')
                ? 'Internal Server Error'
                : $e->getMessage();

            return $this->errorResponse($message, 500, 'SERVER_ERROR');
        }
    }
}