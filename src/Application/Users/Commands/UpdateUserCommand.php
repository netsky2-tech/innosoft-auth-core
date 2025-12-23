<?php

namespace InnoSoft\AuthCore\Application\Users\Commands;

use InnoSoft\AuthCore\UI\Http\Requests\User\UpdateUserRequest;

final readonly class UpdateUserCommand
{
    public function __construct(
        public string $userId,
        public ?string $name,
        public ?string $email,
        public ?string $password,
    ) {}
}