<?php

namespace InnoSoft\AuthCore\Application\Users\Handlers;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InnoSoft\AuthCore\Application\Users\Commands\UpdateUserCommand;
use InnoSoft\AuthCore\Domain\Shared\DomainEventBus;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Domain\Users\Events\UserUpdated;
use InnoSoft\AuthCore\Domain\Users\Exceptions\UserAlreadyExistsException;
use InnoSoft\AuthCore\Domain\Users\Exceptions\UserNotFoundException;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use InnoSoft\AuthCore\Domain\Users\ValueObjects\EmailAddress;

final readonly class UpdateUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private Hasher $hasher,
        private DomainEventBus $domainEventBus
    ){}

    /**
     * @throws UserNotFoundException
     */
    public function __invoke(UpdateUserCommand $command): User
    {
        $user = $this->userRepository->findById($command->userId);

        if (!$user) {
            throw new UserNotFoundException("User with ID {$command->userId} not found.");
        }

        return DB::transaction(function () use ($user, $command) {
            if ($command->name !== null) {
                $user->updateName($command->name);
            }

            if ($command->email !== null) {
                $this->processEmailChange($user, $command->email);
            }

            if ($command->password !== null) {
                $hashedPassword = $this->hasher->make($command->password);
                $user->updatePassword($hashedPassword);
            }

            $this->userRepository->save($user);

            $this->domainEventBus->publish(...$user->pullDomainEvents());

            return $user;
        });

    }

    /**
     * @throws UserAlreadyExistsException
     */
    private function processEmailChange(User $user, string $newEmailString): void
    {
        $newEmail = new EmailAddress($newEmailString);

        if ($user->getEmail()->equals($newEmail)) {
            return;
        }

        if ($this->userRepository->existsByEmail($newEmail->getValue())) {
            throw new UserAlreadyExistsException("The email {$newEmail} is already taken.");
        }

        $user->updateEmail($newEmail);
    }
}