<?php

namespace InnoSoft\AuthCore\Infrastructure\Auth;

use InnoSoft\AuthCore\Domain\Auth\Services\PasswordTokenService;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User as EloquentUser;
use Illuminate\Support\Facades\Password;

class LaravelPasswordTokenService implements PasswordTokenService
{
    public function createToken(User $user): string
    {
        $eloquentUser = EloquentUser::find($user->getId());
        return Password::createToken($eloquentUser);
    }

    public function validateToken(User $user, string $token): bool
    {
        $eloquentUser = EloquentUser::find($user->getId());
        return Password::tokenExists($eloquentUser, $token);
    }

    public function deleteToken(User $user): void
    {
        $eloquentUser = EloquentUser::find($user->getId());
        Password::deleteToken($eloquentUser);
    }
}