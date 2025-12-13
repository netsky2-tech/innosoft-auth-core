<?php

namespace InnoSoft\AuthCore\Application\Auth\Commands\Handlers;

use InnoSoft\AuthCore\Application\Auth\Commands\RequestPasswordResetCommand;
use InnoSoft\AuthCore\Domain\Auth\Services\PasswordTokenService;
use InnoSoft\AuthCore\Domain\Users\UserRepository;

final class RequestPasswordResetHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordTokenService $tokenService
    ) {}

    public function handle(RequestPasswordResetCommand $command): ?string
    {
        $user = $this->userRepository->findByEmail($command->email);

        if (!$user) {
            // Return null silently to prevent user enumeration
            return null;
        }

        $token = $this->tokenService->createToken($user);

        // Aquí normalmente dispararíamos un evento:
        // event(new PasswordResetRequested($user->email(), $token));
        // Por ahora retornamos el token para testear, pero en UI NO debemos retornarlo.

        return $token;
    }
}