<?php

namespace InnoSoft\AuthCore\Domain\Users;

use Illuminate\Contracts\Auth\Authenticatable;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
interface UserRepository
{
    public function save(User $user): void;
    public function findByEmail(string $email): ?User;
    public function findById(string $id): ?User;
    public function findAuthenticatableById(string $userId): ?Authenticatable;
}