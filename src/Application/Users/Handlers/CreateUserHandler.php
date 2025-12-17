<?php

namespace InnoSoft\AuthCore\Application\Users\Handlers;

use Exception;
use Illuminate\Support\Facades\Event;
use InnoSoft\AuthCore\Application\Users\Commands\CreateUserCommand;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Domain\Users\Events\UserRegistered;
use InnoSoft\AuthCore\Domain\Users\Exceptions\UserAlreadyExistsException;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use InnoSoft\AuthCore\Domain\Users\ValueObjects\EmailAddress;
use Ramsey\Uuid\Uuid;

readonly class CreateUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
    ){}

    /**
     * @throws UserAlreadyExistsException
     * @throws Exception
     */
    public function __invoke(CreateUserCommand $command): User
    {
        if ($this->userRepository->existsByEmail($command->email)) {
            throw new UserAlreadyExistsException($command->email);
        }

        $email = new EmailAddress($command->email);

        $user = User::register(
            id: Uuid::uuid4()->toString(),
            name: $command->name,
            email: $email,
            passwordHash: $command->password,
        );

        $this->userRepository->save($user);

        Event::dispatch(
            new UserRegistered(
                $user->getId(), $user->getEmail()
            )
        );

        return $user;
    }
}