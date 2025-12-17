<?php

namespace InnoSoft\AuthCore\Application\Users\Commands;

use InnoSoft\AuthCore\UI\Http\Requests\User\RegisterRequest;

readonly class CreateUserCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}

    public static function fromRequest(RegisterRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
        );
    }
}