<?php

namespace InnoSoft\AuthCore\Application\Users\Handlers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use InnoSoft\AuthCore\Application\Users\Commands\DeleteUserCommand;
use InnoSoft\AuthCore\Domain\Users\Events\UserDeleted;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;

final readonly class DeleteUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
    ){}

    public function __invoke(DeleteUserCommand $command): void
    {
        $user = $this->userRepository->findById($command->userId);

        if (!$user) {
            throw new ModelNotFoundException("User with ID {$command->userId} not found for deletion.");
        }

        $this->userRepository->delete($command->userId);

        Event::dispatch(
            new UserDeleted(
                $user->getId(), $user->getEmail()
            )
        );
    }
}