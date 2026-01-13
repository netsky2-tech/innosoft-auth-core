<?php

namespace InnoSoft\AuthCore\Application\Users\Handlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InnoSoft\AuthCore\Application\Users\Commands\AssignRoleToUserCommand;
use InnoSoft\AuthCore\Domain\Roles\Exceptions\RoleNotFoundException;
use InnoSoft\AuthCore\Domain\Roles\RoleRepository;
use InnoSoft\AuthCore\Domain\Users\Exceptions\UserNotFoundException;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;

final readonly class AssignRoleToUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private RoleRepository $roleRepository
    ) {}

    /**
     * @throws UserNotFoundException
     * @throws RoleNotFoundException
     * @throws \Throwable
     */
    public function __invoke(AssignRoleToUserCommand $command): void
    {
        $user = $this->userRepository->findById($command->userId);
        if (!$user) {
            throw new UserNotFoundException($command->userId);
        }

        $roleExists = $this->roleRepository->exists($command->roleName, $command->guardName);
        if (!$roleExists) {
            throw new RoleNotFoundException($command->roleName);
        }

        DB::transaction(function () use ($user, $command) {
            // Assign role via repository (infrastructure layer handling Spatie/Eloquent)
            $this->userRepository->assignRole($user->getId(), $command->roleName, $command->guardName);

            $role = $this->roleRepository->findByName($command->roleName, $command->guardName);
            $roleId = $role ? $role->id : $command->roleName; // Fallback if DTO doesn't have ID or using name as ID

            $user->notifyRoleAssigned($roleId, $command->roleName);
            
            // Dispatch recorded events
            foreach ($user->pullDomainEvents() as $event) {
                Event::dispatch($event);
            }
        });
    }
}
