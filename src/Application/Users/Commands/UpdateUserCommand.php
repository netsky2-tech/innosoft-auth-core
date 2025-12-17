<?php

namespace InnoSoft\AuthCore\Application\Users\Commands;

use InnoSoft\AuthCore\UI\Http\Requests\User\UpdateUserRequest;

final readonly class UpdateUserCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $password,
    ) {}

    public static function fromRequest(string $id, UpdateUserRequest $request): self
    {
        return new self(
            userId: $id,
            name: $request->has('name') ? $request->validated('name') : null,
            email: $request->has('email') ? $request->validated('email') : null,
            password: $request->has('password') ? $request->validated('password') : null,
        );
    }
}