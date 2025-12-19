<?php

namespace InnoSoft\AuthCore\Application\Auth\Handlers;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use InnoSoft\AuthCore\Application\Auth\Commands\LoginUserCommand;
use InnoSoft\AuthCore\Domain\Auth\Exceptions\TwoFactorRequiredException;
use InnoSoft\AuthCore\Domain\Auth\Services\TokenIssuer;
use InnoSoft\AuthCore\Domain\Users\Exceptions\InvalidCredentialsException;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;


final readonly class LoginUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private TokenIssuer    $tokenIssuer
    ) {}

    /**
     * @throws InvalidCredentialsException
     * @throws TwoFactorRequiredException
     */
    public function handle(LoginUserCommand $command): array
    {
        $user = $this->userRepository->findByEmail($command->email);

        // Fail fast: if user not exists or wrong password
        if (!$user || !Hash::check($command->password, $user->getPasswordHash())) {
            throw new InvalidCredentialsException();
        }

        if($user->hasTwoFactorEnabled()){
            throw new TwoFactorRequiredException($user->getId());
        }

        $eloquentUser = $this->userRepository->findAuthenticatableById($user->getId());
        if ($eloquentUser) {
            Event::dispatch(new Login(
                'sanctum',
                $eloquentUser,
                false
            ));
        }

        // Generate token
        $token = $this->tokenIssuer->issue($user, $command->deviceName);

        return [
            'success' => true,
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