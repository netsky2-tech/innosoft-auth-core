<?php

namespace InnoSoft\AuthCore\Application\Permissions\Handlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InnoSoft\AuthCore\Application\Permissions\Commands\UpdatePermissionCommand;
use InnoSoft\AuthCore\Domain\Permissions\Events\PermissionUpdated;
use InnoSoft\AuthCore\Domain\Permissions\Exceptions\PermissionAlreadyExists;
use InnoSoft\AuthCore\Domain\Permissions\Exceptions\PermissionNotFoundException;
use InnoSoft\AuthCore\Domain\Permissions\PermissionRepository;

final readonly class UpdatePermissionHandler
{
    public function __construct(
        private PermissionRepository $permissionRepository
    ) {}

    /**
     * @throws PermissionNotFoundException
     * @throws PermissionAlreadyExists
     */
    public function __invoke(UpdatePermissionCommand $command): void
    {
        $permission = $this->permissionRepository->findById($command->permissionId);

        if (!$permission) {
            throw new PermissionNotFoundException("Permission with ID {$command->permissionId} not found.");
        }

        if ($permission->name !== $command->newName && $this->permissionRepository->exists($command->newName, $command->guardName)) {
            throw new PermissionAlreadyExists($command->newName);
        }

        DB::transaction(function () use ($permission, $command) {
            $oldName = $permission->name;
            $permission->name = $command->newName;
            $permission->guard_name = $command->guardName;
            $permission->save();

            Event::dispatch(
                new PermissionUpdated(
                    $permission->id,
                    $oldName,
                    $command->newName,
                    $command->guardName
                )
            );
        });
    }
}
