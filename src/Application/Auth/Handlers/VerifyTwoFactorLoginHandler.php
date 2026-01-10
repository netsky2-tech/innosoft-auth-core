<?php

namespace InnoSoft\AuthCore\Application\Auth\Handlers;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use InnoSoft\AuthCore\Application\Auth\Commands\VerifyTwoFactorLoginCommand;
use InnoSoft\AuthCore\Domain\Auth\Events\UserLoggedIn;
use InnoSoft\AuthCore\Domain\Auth\Services\TokenIssuer;
use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorChallengeService;
use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorProvider;
use InnoSoft\AuthCore\Domain\Users\Exceptions\InvalidCredentialsException;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;

readonly class VerifyTwoFactorLoginHandler
{
    public function __construct(
        private TwoFactorChallengeService $challengeService,
        private UserRepository            $userRepository,
        private TwoFactorProvider         $twoFactorProvider,
        private TokenIssuer               $tokenIssuer
    ) {}

    /**
     * @throws InvalidCredentialsException
     */
    public function handle(VerifyTwoFactorLoginCommand $command): array
    {
        // 1. Validate challenge
        $userId = $this->challengeService->verifyChallenge($command->challengeToken);
        if (!$userId) {
            throw new InvalidCredentialsException();
        }

        $user = $this->userRepository->findById($userId);

        // 2. Validate TOTP
        if (!$this->twoFactorProvider->verify($user->getTwoFactorSecret(), $command->code)) {
            throw new InvalidCredentialsException();
        }

        $eloquentUser = $this->userRepository->findAuthenticatableById($user->getId());
        if ($eloquentUser) {
            Event::dispatch(new Login(
                'sanctum',
                $eloquentUser,
                false
            ));
        }

        // Dispatch Domain Event
        Event::dispatch(new UserLoggedIn(
            $user->getId(),
            request()->ip() ?? '0.0.0.0',
            request()->userAgent() ?? 'Unknown',
        ));

        // 3. Emit final token
        $token = $this->tokenIssuer->issue($user, $command->deviceName);

        $roles = $eloquentUser ? $eloquentUser->getRoleNames() : [];
        $permissions = $eloquentUser ? $eloquentUser->getAllPermissions()->pluck('name') : [];

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail()->getValue(),
                'roles' => $roles,
                'permissions' => $permissions,
            ]
        ];
    }
}