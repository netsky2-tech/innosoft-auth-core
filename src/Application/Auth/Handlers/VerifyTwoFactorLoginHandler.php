<?php

namespace InnoSoft\AuthCore\Application\Auth\Handlers;

use InnoSoft\AuthCore\Application\Auth\Commands\VerifyTwoFactorLoginCommand;
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

        // 3. Emit final token
        $token = $this->tokenIssuer->issue($user, $command->deviceName);

        return ['access_token' => $token];
    }
}