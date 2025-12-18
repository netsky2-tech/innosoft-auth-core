<?php

namespace InnoSoft\AuthCore\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
use InnoSoft\AuthCore\Domain\Users\Exceptions\InvalidCredentialsException;
use InnoSoft\AuthCore\Domain\Users\Exceptions\UserAlreadyExistsException;
use InnoSoft\AuthCore\Infrastructure\Auth\CacheTwoFactorChallengeService;
use InnoSoft\AuthCore\UI\Http\Requests\ConfirmTwoFactorRequest;
use InnoSoft\AuthCore\UI\Http\Requests\DisableTwoFactorRequest;
use InnoSoft\AuthCore\UI\Http\Requests\EnableTwoFactorRequest;
use InnoSoft\AuthCore\UI\Http\Requests\ForgotPasswordRequest;
use InnoSoft\AuthCore\UI\Http\Requests\LoginRequest;
use InnoSoft\AuthCore\UI\Http\Requests\ResetPasswordRequest;
use InnoSoft\AuthCore\UI\Http\Requests\User\RegisterRequest;
use InnoSoft\AuthCore\UI\Http\Requests\VerifyTwoFactorRequest;
use InnoSoft\AuthCore\UI\Http\Responses\ApiResponse;

class AuthController extends Controller
{
    use ApiResponse;
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
        return $this->successResponse('','User registered successfully.', 201);
    }

    public function login(LoginRequest $request, LoginUserHandler $handler): JsonResponse
    {
        try {
            $result = $handler->handle(new LoginUserCommand(
                email: $request->validated('email'),
                password: $request->validated('password'),
                deviceName: $request->device_name ?? 'unknown'
            ));

            return $this->successResponse($result, 'Logged in successfully', 200);

        } catch (InvalidCredentialsException $e) {
            return $this->errorResponse('Invalid credentials', 401,'AUTH_FAILED', $e->getMessage());
        } catch (TwoFactorRequiredException $e) {
            $challengeToken = $this->challengeService->createChallenge($e->userId);

            return $this->twoFactorRequiredResponse($challengeToken, 300);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request, RequestPasswordResetHandler $handler): JsonResponse
    {
        $handler->handle(new RequestPasswordResetCommand($request->validated('email')));
        return $this->successResponse('','If your email is registered, you will receive a reset link.', 200);
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordHandler $handler): JsonResponse
    {
        try {
            $handler->handle(new ResetPasswordCommand(
                $request->validated('email'),
                $request->validated('token'),
                $request->validated('password')
            ));
            return $this->successResponse('', 'Password has been reset successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred', $e->getCode(), 'EXCEPTION', $e->getMessage());
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

            return $this->successResponse($result, 'Two factor authentication has been verified.', 200);

        } catch (InvalidCredentialsException $e) {
            return $this->errorResponse(
                'Invalid credentials',
                401,
                'INVALID_CREDENTIALS',
                $e->getMessage());
        }
    }

    public function enableTwoFactor(EnableTwoFactorRequest $request, EnableTwoFactorHandler $handler): JsonResponse
    {
        $data = $handler->handle($request->user()->id);
        return $this->successResponse($data, 'Two factor authentication has been enabled.', 200);
    }

    /**
     * @throws ValidationException
     */
    public function confirmTwoFactor(ConfirmTwoFactorRequest $request, ConfirmTwoFactorHandler $handler): JsonResponse
    {

        $data = $handler->handle($request->user()->id, $request->validated('code'));
        return $this->successResponse($data, 'Two factor authentication has been verified.', 200);
    }

    /**
     * @throws ValidationException
     */
    public function disableTwoFactor(DisableTwoFactorRequest $request, DisableTwoFactorHandler $handler): JsonResponse
    {

        $handler->handle($request->user()->id, $request->validated('current_password'));

        return $this->successResponse(null, 'Two factor authentication disabled successfully.', 200);
    }
}