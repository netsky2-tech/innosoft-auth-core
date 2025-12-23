<?php

namespace InnoSoft\AuthCore\Infrastructure\Persistence;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Model;

/**
 *
 */
readonly class EloquentUserRepository implements UserRepository
{

    /**
     * @param Model $model
     */
    public function __construct(
        private Model $model
    ) {}

    /**
     * @param User $user
     * @return void
     */
    public function save(User $user): void
    {
        // Mapping: Domain -> Eloquent (Active Record)
        $recoveryCodes = $user->getTwoFactorRecoveryCodes();

        $this->model->updateOrCreate(
            ['id' => $user->getId()],
            [
                'name' => $user->getName(),
                'email' => $user->getEmail()->getValue(),
                'password' => $user->getPasswordHash(),
                'two_factor_secret' => $user->getTwoFactorSecret(),
                'two_factor_confirmed_at' => $user->getTwoFactorConfirmed() ? now() : null,
                'two_factor_recovery_codes' => $recoveryCodes ? json_encode($recoveryCodes) : null,
            ]
        );
    }

    /**
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        $eloquentUser = $this->model->where('email', $email)->first();

        return $eloquentUser ? $this->mapToDomain($eloquentUser) : null;
    }

    /**
     * @param string $id
     * @return User|null
     */
    public function findById(string $id): ?User
    {
        $eloquentUser = $this->model->where('id', $id)->first();

        return $eloquentUser ? $this->mapToDomain($eloquentUser) : null;
    }

    /**
     * @param string $userId
     * @return Authenticatable|null
     */
    public function findAuthenticatableById(string $userId): ?Authenticatable
    {
        return $this->model->find($userId);
    }

    /**
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 5): LengthAwarePaginator
    {
        $paginator = $this->model->newQuery()->paginate($perPage);

        $paginator->getCollection()->transform(function ($eloquentUser) {
            return $this->mapToDomain($eloquentUser);
        });
        return $paginator;
    }

    /**
     * @param string $email
     * @return bool
     */
    public function existsByEmail(string $email): bool
    {
        return $this->model->where('email', $email)->exists();
    }

    /**
     * @param string $id
     * @return void
     */
    public function delete(string $id): void
    {
        $this->model->where('id',$id)->delete();
    }

    /**
     * @param Model $eloquentUser
     * @return User
     */
    private function mapToDomain(Model $eloquentUser): User
    {
        $recoveryCodes = $eloquentUser->two_factor_recovery_codes;

        if (is_string($recoveryCodes)) {
            $recoveryCodes = json_decode($recoveryCodes, true);
        }

        return User::fromPersistence(
            (string) $eloquentUser->id,
            $eloquentUser->name,
            $eloquentUser->email,
            $eloquentUser->password,
            $eloquentUser->two_factor_secret,
            $eloquentUser->two_factor_confirmed_at !== null,
            $recoveryCodes,
            $eloquentUser->created_at?->toDateTimeImmutable(),
        );
    }

    public function search(int $page, int $perPage, ?string $term, string $sortBy): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $allowedSorts = ['name', 'email', 'created_at'];
        $sortField = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';

        $query->orderBy($sortField, 'desc');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}