<?php

namespace InnoSoft\AuthCore\Application\Permissions\Handlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InnoSoft\AuthCore\Application\Permissions\Commands\DeletePermissionCommand;
use InnoSoft\AuthCore\Domain\Permissions\Events\PermissionDeleted;
use InnoSoft\AuthCore\Domain\Permissions\Exceptions\PermissionNotFoundException;
use InnoSoft\AuthCore\Domain\Permissions\PermissionRepository;

final readonly class DeletePermissionHandler
{
    public function __construct(
        private PermissionRepository $permissionRepository
    ) {}

    /**
     * @throws PermissionNotFoundException
     */
    public function __invoke(DeletePermissionCommand $command): void
    {
        $permission = $this->permissionRepository->findById($command->permissionId);

        if (!$permission) {
            throw new PermissionNotFoundException("Permission with ID {$command->permissionId} not found.");
        }

        DB::transaction(function () use ($command, $permission) {
            $this->permissionRepository->delete($command->permissionId);

            Event::dispatch(
                new PermissionDeleted(
                    $permission->name,
                    $permission->guard_name
                )
            );
        });
    }
}
