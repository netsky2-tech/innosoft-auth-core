<?php

namespace InnoSoft\AuthCore\Domain\Users\Events;

readonly class PasswordResetCompleted
{
    public function __construct(private string $userId, private string $email) {}

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}