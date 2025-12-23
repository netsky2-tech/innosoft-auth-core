<?php

namespace InnoSoft\AuthCore\Domain\Users\Repositories;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;

interface UserRepository
{
    public function save(User $user): void;

    public function findByEmail(string $email): ?User;

    public function findById(string $id): ?User;

    public function findAuthenticatableById(string $userId): ?Authenticatable;

    public function paginate(int $perPage = 5): LengthAwarePaginator;

    public function existsByEmail(string $email): bool;

    public function delete(string $id): void;

    public function search(
        int     $page,
        int     $perPage,
        ?string $term,
        string  $sortBy
    ): LengthAwarePaginator;
}