<?php

namespace InnoSoft\AuthCore\Application\Roles\Handlers;

use Illuminate\Support\Facades\DB;
use InnoSoft\AuthCore\Application\Roles\Commands\RevokeRolePermissionCommand;
use InnoSoft\AuthCore\Domain\Roles\Exceptions\RoleNotFoundException;
use InnoSoft\AuthCore\Domain\Roles\RoleRepository;

final readonly class RevokeRolePermissionHandler
{
    public function __construct(
        private RoleRepository $roleRepository
    ) {}

    /**
     * @throws RoleNotFoundException
     */
    public function __invoke(RevokeRolePermissionCommand $command): void
    {
        $role = $this->roleRepository->findByName($command->roleName, $command->guardName);

        if (!$role) {
            throw new RoleNotFoundException($command->roleName);
        }

        DB::transaction(function () use ($role, $command) {
            $role->revokePermissionTo($command->permissionName);
        });
    }
}
