<?php

namespace InnoSoft\AuthCore\Infrastructure\Persistence;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Model;

readonly class EloquentUserRepository implements UserRepository
{

    public function __construct(
        private Model $model
    ) {}

    public function save(User $user): void
    {
        // Mapping: Domain -> Eloquent (Active Record)
        $this->model->updateOrCreate(
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
        $eloquentUser = $this->model->where('email', $email)->first();

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
        $eloquentUser = $this->model->where('id', $id)->first();

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
        return $this->model->find($userId);
    }

    public function paginate(int $perPage = 5): LengthAwarePaginator
    {
        return $this->model->newQuery()->paginate($perPage);
    }

    public function existsByEmail(string $email): bool
    {
        return $this->model->where('email', $email)->exists();
    }

    public function delete(string $id): void
    {
        $this->model->find($id)->delete();
    }
}