<?php

namespace InnoSoft\AuthCore\Infrastructure\Auth;

use InnoSoft\AuthCore\Domain\Auth\Services\TokenIssuer;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User as DomainUser;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User as EloquentUser;

class SanctumTokenIssuer implements TokenIssuer
{

    public function issue(DomainUser $user, string $deviceName): string
    {
        // search for eloquent models of user domain
        $eloquentUser = EloquentUser::find($user->getId());

        if (!$eloquentUser) {
            // extreme case: exist on repository but not in DB
            throw new \RuntimeException("User persistence mismatch");
        }

        // Use Sanctum to generate token
        // get expiration from config
        $expiration = now()->addMinutes(config('auth-core.token_expiration', 1440));

        $token = $eloquentUser->createToken(
            name: $deviceName,
            expiresAt: $expiration
        );

        return $token->plainTextToken;
    }
}