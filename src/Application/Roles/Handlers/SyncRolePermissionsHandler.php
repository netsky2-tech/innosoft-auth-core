<?php

namespace InnoSoft\AuthCore\Application\Roles\Handlers;

use Illuminate\Support\Facades\DB;
use InnoSoft\AuthCore\Application\Roles\Commands\SyncRolePermissionsCommand;
use InnoSoft\AuthCore\Domain\Roles\Exceptions\RoleNotFoundException;
use InnoSoft\AuthCore\Domain\Roles\RoleRepository;

final readonly class SyncRolePermissionsHandler
{
    public function __construct(
        private RoleRepository $roleRepository
    ) {}

    /**
     * @throws RoleNotFoundException|\Throwable
     */
    public function __invoke(SyncRolePermissionsCommand $command): void
    {
        $role = $this->roleRepository->findByName($command->roleName, $command->guardName);

        if (!$role) {
            throw new RoleNotFoundException($command->roleName);
        }

        DB::transaction(function () use ($command) {
            $this->roleRepository->syncPermissions(
                $command->roleName,
                $command->permissions,
                $command->guardName
            );
        });
    }
}
