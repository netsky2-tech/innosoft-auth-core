<?php

namespace InnoSoft\AuthCore\UI\Http\Responses;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Standard Success Response (200 OK)
     */
    protected function successResponse(mixed $data, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ], $code);
    }

    /**
     * Error response (4xx, 5xx)
     */
    protected function errorResponse(string $message, int $code, string $errorCode = 'GENERIC_ERROR', array|string|null $errors = null): JsonResponse
    {
        $response = [
            'success'    => false,
            'error_code' => $errorCode,
            'message'    => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Specific 2FA response
     */
    protected function twoFactorRequiredResponse(string $tempToken, int $expiresIn): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status'  => '2fa_required',
            'message' => 'Two factor authentication required.',
            'data'    => [
                'temp_token' => $tempToken,
                'expires_in' => $expiresIn
            ]
        ], 200);
    }
}