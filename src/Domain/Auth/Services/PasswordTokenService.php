<?php

namespace InnoSoft\AuthCore\Domain\Auth\Services;

use InnoSoft\AuthCore\Domain\Users\Aggregates\User;

interface PasswordTokenService
{
    public function createToken(User $user): string;
    public function validateToken(User $user, string $token): bool;
    public function deleteToken(User $user): void;
}