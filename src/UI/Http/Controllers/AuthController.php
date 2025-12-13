<?php

namespace InnoSoft\AuthCore\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use InnoSoft\AuthCore\Application\Auth\Commands\Handlers\LoginUserHandler;
use InnoSoft\AuthCore\Application\Auth\Commands\Handlers\RegisterUserHandler;
use InnoSoft\AuthCore\Application\Auth\Commands\Handlers\RequestPasswordResetHandler;
use InnoSoft\AuthCore\Application\Auth\Commands\Handlers\ResetPasswordHandler;
use InnoSoft\AuthCore\Application\Auth\Commands\LoginUserCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\RegisterUserCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\RequestPasswordResetCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\ResetPasswordCommand;
use InnoSoft\AuthCore\Domain\Users\Exceptions\InvalidCredentialsException;
use InnoSoft\AuthCore\Domain\Users\Exceptions\UserAlreadyExistsException;
use InnoSoft\AuthCore\UI\Http\Requests\ForgotPasswordRequest;
use InnoSoft\AuthCore\UI\Http\Requests\LoginRequest;
use InnoSoft\AuthCore\UI\Http\Requests\RegisterRequest;
use InnoSoft\AuthCore\UI\Http\Requests\ResetPasswordRequest;

class AuthController extends Controller
{
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
}