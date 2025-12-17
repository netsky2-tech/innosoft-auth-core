<?php

namespace InnoSoft\AuthCore\Application\Roles\Handlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InnoSoft\AuthCore\Application\Roles\Commands\CreateRoleCommand;
use InnoSoft\AuthCore\Domain\Roles\Events\RoleRegistered;
use InnoSoft\AuthCore\Domain\Roles\Exceptions\RoleAlreadyExists;
use InnoSoft\AuthCore\Domain\Roles\RoleDTO;
use InnoSoft\AuthCore\Domain\Roles\RoleRepository;
use Ramsey\Uuid\Uuid;

readonly class CreateRoleHandler
{
    public function __construct(
        private RoleRepository $roleRepository
    )
    {
    }

    /**
     * @throws RoleAlreadyExists
     */
    public function __invoke(CreateRoleCommand $command): void
    {
        if ($this->roleRepository->exists($command->name, $command->guardName)) {
            throw new RoleAlreadyExists($command->name);
        }

        DB::transaction(function () use ($command) {

            $dto = new RoleDTO(
                $command->name,
                $command->guardName,
                $command->permissions
            );

            $this->roleRepository->save($dto);

            Event::dispatch(
                new RoleRegistered(
                    $dto->name,
                    $dto->guardName,
                )
            );
        });
    }
}