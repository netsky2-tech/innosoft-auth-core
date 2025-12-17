<?php

namespace InnoSoft\AuthCore\Application\Auth\Handlers;

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
    public function handle(string $challengeToken, string $code, string $device): array
    {
        // 1. Validate challenge
        $userId = $this->challengeService->verifyChallenge($challengeToken);
        if (!$userId) {
            throw new InvalidCredentialsException();
        }

        $user = $this->userRepository->findById($userId);

        // 2. Validate TOTP
        if (!$this->twoFactorProvider->verify($user->getTwoFactorSecret(), $code)) {
            throw new InvalidCredentialsException();
        }

        // 3. Emit final token
        $token = $this->tokenIssuer->issue($user, $device);

        return ['access_token' => $token];
    }
}