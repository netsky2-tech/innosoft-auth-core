<?php

namespace InnoSoft\AuthCore\Application\Users\Commands;

final readonly class AssignRoleToUserCommand
{
    public function __construct(
        public string $userId,
        public string $roleName,
        public string $guardName = 'api'
    ) {}
}
