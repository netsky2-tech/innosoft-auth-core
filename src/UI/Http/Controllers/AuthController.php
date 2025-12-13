<?php

namespace InnoSoft\AuthCore\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use InnoSoft\AuthCore\Application\Auth\Commands\Handlers\ConfirmTwoFactorHandler;
use InnoSoft\AuthCore\Application\Auth\Commands\Handlers\DisableTwoFactorHandler;
use InnoSoft\AuthCore\Application\Auth\Commands\Handlers\EnableTwoFactorHandler;
use InnoSoft\AuthCore\Application\Auth\Commands\Handlers\LoginUserHandler;
use InnoSoft\AuthCore\Application\Auth\Commands\Handlers\RegisterUserHandler;
use InnoSoft\AuthCore\Application\Auth\Commands\Handlers\RequestPasswordResetHandler;
use InnoSoft\AuthCore\Application\Auth\Commands\Handlers\ResetPasswordHandler;
use InnoSoft\AuthCore\Application\Auth\Commands\Handlers\VerifyTwoFactorLoginHandler;
use InnoSoft\AuthCore\Application\Auth\Commands\LoginUserCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\RegisterUserCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\RequestPasswordResetCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\ResetPasswordCommand;
use InnoSoft\AuthCore\Domain\Auth\Exceptions\TwoFactorRequiredException;
use InnoSoft\AuthCore\Domain\Users\Exceptions\InvalidCredentialsException;
use InnoSoft\AuthCore\Domain\Users\Exceptions\UserAlreadyExistsException;
use InnoSoft\AuthCore\Infrastructure\Auth\CacheTwoFactorChallengeService;
use InnoSoft\AuthCore\UI\Http\Requests\ForgotPasswordRequest;
use InnoSoft\AuthCore\UI\Http\Requests\LoginRequest;
use InnoSoft\AuthCore\UI\Http\Requests\RegisterRequest;
use InnoSoft\AuthCore\UI\Http\Requests\ResetPasswordRequest;
use InnoSoft\AuthCore\UI\Http\Requests\VerifyTwoFactorRequest;

class AuthController extends Controller
{
    public function __construct(private readonly CacheTwoFactorChallengeService $challengeService)
    {}

    /**
     * @throws UserAlreadyExistsException
     */
    public function register(RegisterRequest $request, RegisterUserHandler $handler): JsonResponse
    {
        // Mapping Request -> Command
        $command = new RegisterUserCommand(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password')
        );

        // Execution
        $handler->handle($command);

        // Response
        return response()->json([
            'message' => 'User registered successfully',
        ], 201);
    }

    public function login(LoginRequest $request, LoginUserHandler $handler): JsonResponse
    {
        try {
            $result = $handler->handle(new LoginUserCommand(
                email: $request->validated('email'),
                password: $request->validated('password'),
                deviceName: $request->device_name ?? 'unknown'
            ));

            return response()->json($result);

        } catch (InvalidCredentialsException $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        } catch (TwoFactorRequiredException $e) {
            $challengeToken = $this->challengeService->createChallenge($e->userId);

            return response()->json([
                'message' => 'Two-factor authentication required',
                'requires_two_factor' => true,
                'challenge_token' => $challengeToken
            ]);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request, RequestPasswordResetHandler $handler): JsonResponse
    {
        $handler->handle(new RequestPasswordResetCommand($request->validated('email')));
        return response()->json(['message' => 'If your email is registered, you will receive a reset link.']);
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordHandler $handler): JsonResponse
    {
        try {
            $handler->handle(new ResetPasswordCommand(
                $request->validated('email'), $request->validated('token'), $request->validated('password')
            ));
            return response()->json(['message' => 'Password has been reset successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function verifyTwoFactor(VerifyTwoFactorRequest $request, VerifyTwoFactorLoginHandler $handler): JsonResponse
    {
        try {
            $result = $handler->handle(
                $request->validated('challenge_token'),
                $request->validated('code'),
                $request->validated('device_name')
            );

            return response()->json($result);

        } catch (InvalidCredentialsException $e) {
            return response()->json(['message' => 'Invalid code or expired session.'], 401);
        }
    }

    public function enableTwoFactor(Request $request, EnableTwoFactorHandler $handler): JsonResponse
    {
        $data = $handler->handle($request->user()->id);
        return response()->json($data);
    }

    /**
     * @throws ValidationException
     */
    public function confirmTwoFactor(Request $request, ConfirmTwoFactorHandler $handler
    ): JsonResponse {
        $request->validate(['code' => 'required|string']);

        $data = $handler->handle($request->user()->id, $request->code);
        return response()->json($data);
    }

    /**
     * @throws ValidationException
     */
    public function disableTwoFactor(Request $request, DisableTwoFactorHandler $handler): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string'
        ]);

        $handler->handle($request->user()->id, $request->current_password);

        return response()->json([
            'message' => 'Two factor authentication disabled successfully.'
        ]);
    }
}