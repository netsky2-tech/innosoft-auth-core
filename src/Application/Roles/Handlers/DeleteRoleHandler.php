<?php

namespace InnoSoft\AuthCore\Application\Roles\Handlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InnoSoft\AuthCore\Application\Roles\Commands\DeleteRoleCommand;
use InnoSoft\AuthCore\Domain\Roles\Events\RoleDeleted;
use InnoSoft\AuthCore\Domain\Roles\Exceptions\RoleNotFoundException;
use InnoSoft\AuthCore\Domain\Roles\RoleRepository;

final readonly class DeleteRoleHandler
{
    public function __construct(
        private RoleRepository $roleRepository
    ) {}

    /**
     * @throws RoleNotFoundException
     */
    public function __invoke(DeleteRoleCommand $command): void
    {
        $role = $this->roleRepository->findById($command->roleId);

        if (!$role) {
            throw new RoleNotFoundException("Role with ID {$command->roleId} not found.");
        }

        DB::transaction(function () use ($command, $role) {
            $this->roleRepository->delete($command->roleId);

            Event::dispatch(
                new RoleDeleted(
                    $role->name,
                    $role->guard_name
                )
            );
        });
    }
}
