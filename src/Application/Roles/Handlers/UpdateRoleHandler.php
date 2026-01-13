<?php

namespace InnoSoft\AuthCore\Application\Roles\Handlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InnoSoft\AuthCore\Application\Roles\Commands\UpdateRoleCommand;
use InnoSoft\AuthCore\Domain\Roles\Events\RoleUpdated;
use InnoSoft\AuthCore\Domain\Roles\Exceptions\RoleAlreadyExists;
use InnoSoft\AuthCore\Domain\Roles\Exceptions\RoleNotFoundException;
use InnoSoft\AuthCore\Domain\Roles\RoleRepository;

final readonly class UpdateRoleHandler
{
    public function __construct(
        private RoleRepository $roleRepository
    ) {}

    /**
     * @throws RoleNotFoundException
     * @throws RoleAlreadyExists
     */
    public function __invoke(UpdateRoleCommand $command): void
    {
        $role = $this->roleRepository->findById($command->roleId);

        if (!$role) {
            throw new RoleNotFoundException("Role with ID {$command->roleId} not found.");
        }

        if ($role->name !== $command->newName && $this->roleRepository->exists($command->newName, $command->guardName)) {
            throw new RoleAlreadyExists($command->newName);
        }

        DB::transaction(function () use ($role, $command) {
            $oldName = $role->name;
            $role->name = $command->newName;
            $role->guard_name = $command->guardName;
            $role->save();

            Event::dispatch(
                new RoleUpdated(
                    $role->id,
                    $oldName,
                    $command->newName,
                    $command->guardName
                )
            );
        });
    }
}
