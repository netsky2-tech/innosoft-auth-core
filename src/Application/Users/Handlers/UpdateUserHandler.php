<?php

namespace InnoSoft\AuthCore\Application\Users\Handlers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use InnoSoft\AuthCore\Application\Users\Commands\UpdateUserCommand;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Domain\Users\Exceptions\UserAlreadyExistsException;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use InnoSoft\AuthCore\Domain\Users\ValueObjects\EmailAddress;

final readonly class UpdateUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
    ){}

    /**
     * @throws UserAlreadyExistsException
     */
    public function __invoke(UpdateUserCommand $command): User
    {
        $user = $this->userRepository->findById($command->userId);

        if (!$user) {
            throw new ModelNotFoundException("User with ID {$command->userId} not found.");
        }

        if ($command->name !== null) {
            $user->updateName($command->name);
        }

        if ($command->email !== null) {

            $newEmail = new EmailAddress($command->email);

            // Validate only if the email is different
            if ($user->getEmail()->getValue() !== $newEmail->getValue()) {
                if ($this->userRepository->existsByEmail($newEmail->getValue())) {
                    throw new UserAlreadyExistsException("The new email {$newEmail->getValue()} is already taken by another user.");
                }

                $user->updateEmail($newEmail);
            }

        }

        if ($command->password !== null) {
            $user->updatePassword(Hash::make($command->password));
        }

        $this->userRepository->save($user);

        return $user;

    }
}