<?php

namespace InnoSoft\AuthCore\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use InnoSoft\AuthCore\Application\Auth\Commands\LoginUserCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\RegisterUserCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\RequestPasswordResetCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\ResetPasswordCommand;
use InnoSoft\AuthCore\Application\Auth\Handlers\ConfirmTwoFactorHandler;
use InnoSoft\AuthCore\Application\Auth\Handlers\DisableTwoFactorHandler;
use InnoSoft\AuthCore\Application\Auth\Handlers\EnableTwoFactorHandler;
use InnoSoft\AuthCore\Application\Auth\Handlers\LoginUserHandler;
use InnoSoft\AuthCore\Application\Auth\Handlers\RegisterUserHandler;
use InnoSoft\AuthCore\Application\Auth\Handlers\RequestPasswordResetHandler;
use InnoSoft\AuthCore\Application\Auth\Handlers\ResetPasswordHandler;
use InnoSoft\AuthCore\Application\Auth\Handlers\VerifyTwoFactorLoginHandler;
use InnoSoft\AuthCore\Domain\Auth\Exceptions\TwoFactorRequiredException;
use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorChallengeService;
use InnoSoft\AuthCore\Domain\Users\Exceptions\InvalidCredentialsException;
use InnoSoft\AuthCore\Domain\Users\Exceptions\UserAlreadyExistsException;
use InnoSoft\AuthCore\UI\Http\Requests\ConfirmTwoFactorRequest;
use InnoSoft\AuthCore\UI\Http\Requests\DisableTwoFactorRequest;
use InnoSoft\AuthCore\UI\Http\Requests\EnableTwoFactorRequest;
use InnoSoft\AuthCore\UI\Http\Requests\ForgotPasswordRequest;
use InnoSoft\AuthCore\UI\Http\Requests\LoginRequest;
use InnoSoft\AuthCore\UI\Http\Requests\ResetPasswordRequest;
use InnoSoft\AuthCore\UI\Http\Requests\User\CreateUserRequest;
use InnoSoft\AuthCore\UI\Http\Requests\VerifyTwoFactorRequest;
use InnoSoft\AuthCore\UI\Http\Responses\ApiResponse;
use InnoSoft\AuthCore\UI\Http\Traits\HandlesApiExecution;

class AuthController extends Controller
{
    use HandlesApiExecution, ApiResponse;
    public function __construct(private readonly TwoFactorChallengeService $challengeService)
    {}

    /**
     */
    public function register(CreateUserRequest $request, RegisterUserHandler $handler): JsonResponse
    {
        return $this->safeExecute(function () use ($request, $handler) {

            // Mapping Request -> Command
            $command = new RegisterUserCommand(
                name: $request->validated('name'),
                email: $request->validated('email'),
                password: $request->validated('password')
            );

            // Execution
            $handler->handle($command);

        }, 'User registered successfully.', 201);

    }

    public function login(LoginRequest $request, LoginUserHandler $handler): JsonResponse
    {
        return $this->safeExecute(function () use ($request, $handler) {
            try {
                return $handler->handle(new LoginUserCommand(
                    email: $request->validated('email'),
                    password: $request->validated('password'),
                    deviceName: $request->device_name ?? 'unknown'
                ));
            } catch (TwoFactorRequiredException $e) {
                $challengeToken = $this->challengeService->createChallenge($e->userId);

                return $this->twoFactorRequiredResponse($challengeToken, 300);
            }
        }, 'Logged in successfully', 200);
    }

    public function forgotPassword(ForgotPasswordRequest $request, RequestPasswordResetHandler $handler): JsonResponse
    {
        return $this->safeExecute(function () use ($request, $handler) {

            $handler->handle(new RequestPasswordResetCommand($request->validated('email')));

        },'If your email is registered, you will receive a reset link.', 200);
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordHandler $handler): JsonResponse
    {
        return $this->safeExecute(function () use ($request, $handler) {
            try {
                return $handler->handle(new ResetPasswordCommand(
                    $request->validated('email'),
                    $request->validated('token'),
                    $request->validated('password')
                ));
            } catch (\Exception $e) {
                return $this->errorResponse('An error occurred', $e->getCode(), 'EXCEPTION', $e->getMessage());
            }
        },'Password has been reset successfully', 200);

    }

    public function verifyTwoFactor(VerifyTwoFactorRequest $request, VerifyTwoFactorLoginHandler $handler): JsonResponse
    {
        return $this->safeExecute(function () use ($request, $handler) {
            try {
                return $handler->handle(
                    $request->validated('challenge_token'),
                    $request->validated('code'),
                    $request->validated('device_name')
                );
            } catch (InvalidCredentialsException $e) {
                return $this->errorResponse(
                    'Invalid credentials',
                    $e->getCode(),
                    'EXCEPTION',
                    $e->getMessage());

            }
        }, 'Two factor authentication has been verified.', 200);
    }

    public function enableTwoFactor(EnableTwoFactorRequest $request, EnableTwoFactorHandler $handler): JsonResponse
    {
        return $this->safeExecute(function () use ($request, $handler) {
            return $handler->handle($request->user()->id);
        }, 'Two factor authentication has been enabled.', 200);
    }

    /**
     */
    public function confirmTwoFactor(ConfirmTwoFactorRequest $request, ConfirmTwoFactorHandler $handler): JsonResponse
    {
        return $this->safeExecute(function () use ($request, $handler) {
            return $handler->handle($request->user()->id, $request->validated('code'));
        }, 'Two factor authentication has been verified.', 200);
    }

    /**
     */
    public function disableTwoFactor(DisableTwoFactorRequest $request, DisableTwoFactorHandler $handler): JsonResponse
    {
        return $this->safeExecute(function () use ($request, $handler) {
            return $handler->handle($request->user()->id, $request->validated('current_password'));

        },'Two factor authentication disabled successfully.', 200);
    }
}