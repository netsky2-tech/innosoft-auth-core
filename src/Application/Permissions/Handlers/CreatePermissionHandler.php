<?php

namespace InnoSoft\AuthCore\Application\Permissions\Handlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InnoSoft\AuthCore\Application\Permissions\Commands\CreatePermissionCommand;
use InnoSoft\AuthCore\Domain\Permissions\Events\PermissionRegistered;
use InnoSoft\AuthCore\Domain\Permissions\Exceptions\PermissionAlreadyExists;
use InnoSoft\AuthCore\Domain\Permissions\PermissionDTO;
use InnoSoft\AuthCore\Domain\Permissions\PermissionRepository;

final readonly class CreatePermissionHandler
{
    public function __construct(
        private PermissionRepository $permissionRepository
    ) {}

    /**
     * @throws PermissionAlreadyExists
     */
    public function __invoke(CreatePermissionCommand $command): void
    {
        if ($this->permissionRepository->exists($command->name, $command->guardName)) {
            throw new PermissionAlreadyExists($command->name);
        }

        DB::transaction(function () use ($command) {
            $dto = new PermissionDTO(
                $command->name,
                $command->guardName
            );

            $this->permissionRepository->save($dto);

            Event::dispatch(
                new PermissionRegistered(
                    $dto->name,
                    $dto->guardName
                )
            );
        });
    }
}
