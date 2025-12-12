<?php

namespace InnoSoft\AuthCore\Domain\Auth\Services;

use InnoSoft\AuthCore\Domain\Users\Aggregates\User;

interface TokenIssuer
{
    public function issue(User $user, string $deviceName): string;
}