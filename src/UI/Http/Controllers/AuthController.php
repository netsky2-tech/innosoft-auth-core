<?php

namespace InnoSoft\AuthCore\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use InnoSoft\AuthCore\Application\Auth\Commands\Handlers\LoginUserHandler;
use InnoSoft\AuthCore\Application\Auth\Commands\Handlers\RegisterUserHandler;
use InnoSoft\AuthCore\Application\Auth\Commands\LoginUserCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\RegisterUserCommand;
use InnoSoft\AuthCore\Domain\Users\Exceptions\InvalidCredentialsException;
use InnoSoft\AuthCore\Domain\Users\Exceptions\UserAlreadyExistsException;
use InnoSoft\AuthCore\UI\Http\Requests\LoginRequest;
use InnoSoft\AuthCore\UI\Http\Requests\RegisterRequest;

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
}