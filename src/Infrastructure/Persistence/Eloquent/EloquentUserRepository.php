<?php

namespace InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent;

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
            $eloquentUser->password
        );
    }
}