<?php

namespace InnoSoft\AuthCore\Domain\Auth\Services;

interface TwoFactorChallengeService {
    public function createChallenge(string $userId): string;
    public function verifyChallenge(string $token): ?string;
}