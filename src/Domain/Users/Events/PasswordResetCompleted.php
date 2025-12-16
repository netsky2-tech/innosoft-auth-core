<?php

namespace InnoSoft\AuthCore\Domain\Users\Events;

class PasswordResetCompleted
{
    public function __construct(public string $userId, public string $email) {}
}