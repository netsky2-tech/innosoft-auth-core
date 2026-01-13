<?php

namespace InnoSoft\AuthCore\Application\Users\Commands;

final readonly class RevokeRoleFromUserCommand
{
    public function __construct(
        public string $userId,
        public string $roleName,
        public string $guardName = 'api'
    ) {}
}
