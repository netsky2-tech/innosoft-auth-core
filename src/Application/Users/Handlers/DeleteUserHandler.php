<?php

namespace InnoSoft\AuthCore\Application\Users\Handlers;

use Illuminate\Support\Facades\DB;
use InnoSoft\AuthCore\Application\Users\Commands\DeleteUserCommand;
use InnoSoft\AuthCore\Domain\Shared\DomainEventBus;
use InnoSoft\AuthCore\Domain\Users\Exceptions\UserNotFoundException;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use Throwable;

final readonly class DeleteUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private DomainEventBus $domainEventBus,
    ){}

    /**
     * @throws UserNotFoundException
     * @throws Throwable
     */
    public function __invoke(DeleteUserCommand $command): void
    {
        $user = $this->userRepository->findById($command->userId);

        if (!$user) {
            throw new UserNotFoundException($command->userId);
        }

        DB::transaction(function () use ($user) {

            $user->delete();

            $this->userRepository->save($user);

            $this->domainEventBus->publish(...$user->pullDomainEvents());
        });
    }
}