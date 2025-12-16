<?php

namespace InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent;

use Illuminate\Contracts\Auth\Authenticatable;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Domain\Users\UserRepository;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User as EloquentModel;

class EloquentUserRepository implements UserRepository
{

    public function save(User $user): void
    {
        // Mapping: Domain -> Eloquent (Active Record)
        EloquentModel::updateOrCreate(
            ['id' => $user->getId()],
            [
                'name' => $user->getName(),
                'email' => $user->getEmail()->getValue(),
                'password' => $user->getPasswordHash(),
                'two_factor_secret' => $user->getTwoFactorSecret(),
                'two_factor_confirmed_at' => $user->getTwoFactorConfirmed() ? now() : null,
                'two_factor_recovery_codes' => $user->getTwoFactorRecoveryCodes()
            ]
        );
    }

    public function findByEmail(string $email): ?User
    {
        $eloquentUser = EloquentModel::where('email', $email)->first();

        if (!$eloquentUser) {
            return null;
        }

        // mapping: Eloquent -> Domain (Reconstitute)
        return User::fromPersistence(
            $eloquentUser->id,
            $eloquentUser->name,
            $eloquentUser->email,
            $eloquentUser->password,
            $eloquentUser->two_factor_secret,
            $eloquentUser->two_factor_confirmed_at != null,
            $eloquentUser->two_factor_recovery_codes
        );
    }

    public function findById(string $id): ?User
    {
        $eloquentUser = EloquentModel::where('id', $id)->first();

        if (!$eloquentUser) {
            return null;
        }

        // mapping: Eloquent -> Domain (Reconstitute)
        return User::fromPersistence(
            $eloquentUser->id,
            $eloquentUser->name,
            $eloquentUser->email,
            $eloquentUser->password,
            $eloquentUser->two_factor_secret,
            $eloquentUser->two_factor_confirmed_at != null,
            $eloquentUser->two_factor_recovery_codes
        );
    }
    public function findAuthenticatableById(string $userId): ?Authenticatable
    {
        return \InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User::find($userId);
    }
}