<?php

namespace InnoSoft\AuthCore\Application\Users\Handlers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InnoSoft\AuthCore\Application\Users\Commands\DeleteUserCommand;
use InnoSoft\AuthCore\Domain\Shared\DomainEventBus;
use InnoSoft\AuthCore\Domain\Users\Events\UserDeleted;
use InnoSoft\AuthCore\Domain\Users\Exceptions\UserNotFoundException;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;

final readonly class DeleteUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private DomainEventBus $domainEventBus,
    ){}

    /**
     * @throws UserNotFoundException
     */
    public function __invoke(DeleteUserCommand $command): void
    {
        $user = $this->userRepository->findById($command->userId);

        if (!$user) {
            throw new UserNotFoundException("User with ID {$command->userId} not found for deletion.");
        }

        DB::transaction(function () use ($user) {

            $user->delete();

            $this->userRepository->delete($user->getId());

            $this->domainEventBus->publish(...$user->pullDomainEvents());
        });
    }
}