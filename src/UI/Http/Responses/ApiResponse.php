<?php

namespace InnoSoft\AuthCore\UI\Http\Responses;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Standard Success Response (200 OK)
     */
    protected function successResponse(mixed $data = null, string $message = null, int $code = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message ?? trans('auth-core::messages.ok'),
        ];
        
        if(!is_null($data)) {
            // If data is an array and has 'access_token', we merge it at the root level
            // This is a common pattern for login responses to keep the structure flat
            if (is_array($data) && isset($data['access_token'])) {
                $payload = array_merge($payload, $data);
            } else {
                $payload['data'] = $data;
            }
        }

        return response()->json($payload, $code);
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
            'message' => trans('auth-core::messages.two_factor_required'),
            'data'    => [
                'temp_token' => $tempToken,
                'expires_in' => $expiresIn
            ]
        ], 200);
    }
}