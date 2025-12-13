<?php

namespace InnoSoft\AuthCore\Infrastructure\Auth;

use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorChallengeService;

class CacheTwoFactorChallengeService implements TwoFactorChallengeService
{

    public function createChallenge(string $userId): string
    {
        $token = '2fa_login_' . \Illuminate\Support\Str::random(40);
        // expire in 5 minutes
        cache()->put($token, $userId, 300);
        return $token;
    }

    public function verifyChallenge(string $token): ?string
    {
        return cache()->pull($token);
    }
}