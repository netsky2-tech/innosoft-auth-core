<?php

namespace InnoSoft\AuthCore\Application\Auth\Commands\Handlers;

use Illuminate\Support\Facades\Hash;
use InnoSoft\AuthCore\Application\Auth\Commands\LoginUserCommand;
use InnoSoft\AuthCore\Domain\Auth\Services\TokenIssuer;
use InnoSoft\AuthCore\Domain\Users\Exceptions\InvalidCredentialsException;
use InnoSoft\AuthCore\Domain\Users\UserRepository;

final class LoginUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private TokenIssuer $tokenIssuer
    ) {}

    /**
     * @throws InvalidCredentialsException
     */
    public function handle(LoginUserCommand $command): array
    {
        $user = $this->userRepository->findByEmail($command->email);

        // Fail fast: if user not exists or wrong password
        if (!$user || !Hash::check($command->password, $user->getPasswordHash())) {
            throw new InvalidCredentialsException();
        }

        // Generate token
        $token = $this->tokenIssuer->issue($user, $command->deviceName);

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail()->getValue(),
            ]
        ];
    }
}