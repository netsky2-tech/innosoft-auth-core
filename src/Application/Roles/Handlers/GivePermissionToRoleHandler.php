<?php

namespace InnoSoft\AuthCore\Application\Roles\Handlers;

use Illuminate\Support\Facades\DB;
use InnoSoft\AuthCore\Application\Roles\Commands\GivePermissionToRoleCommand;
use InnoSoft\AuthCore\Domain\Roles\Exceptions\RoleNotFoundException;
use InnoSoft\AuthCore\Domain\Roles\RoleRepository;

final readonly class GivePermissionToRoleHandler
{
    public function __construct(
        private RoleRepository $roleRepository
    ) {}

    /**
     * @throws RoleNotFoundException
     */
    public function __invoke(GivePermissionToRoleCommand $command): void
    {
        $role = $this->roleRepository->findByName($command->roleName, $command->guardName);

        if (!$role) {
            throw new RoleNotFoundException($command->roleName);
        }

        DB::transaction(function () use ($role, $command) {
            $role->givePermissionTo($command->permissionName);
        });
    }
}
