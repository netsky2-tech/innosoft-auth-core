<?php

namespace InnoSoft\AuthCore\Application\Users\Commands;

use InnoSoft\AuthCore\UI\Http\Requests\User\DeleteUserRequest;

final readonly class DeleteUserCommand
{
    public function __construct(
        public string $userId
    ) {}

    public static function fromRequest(DeleteUserRequest $request): self
    {
        return new self(
            userId: $request->validated('id'),
        );
    }
}