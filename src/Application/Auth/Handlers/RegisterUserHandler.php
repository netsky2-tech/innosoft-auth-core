<?php

namespace InnoSoft\AuthCore\Application\Auth\Handlers;

use Illuminate\Support\Facades\Hash;
use InnoSoft\AuthCore\Application\Auth\Commands\RegisterUserCommand;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Domain\Users\Exceptions\UserAlreadyExistsException;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use InnoSoft\AuthCore\Domain\Users\ValueObjects\EmailAddress;
use Ramsey\Uuid\Uuid;

final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * @throws UserAlreadyExistsException
     */
    public function handle(RegisterUserCommand $command): void
    {
        // 1. Verify duplicate
        if ($this->userRepository->findByEmail($command->email)) {
            throw new UserAlreadyExistsException($command->email);
        }

        // 2. Hashing password
        $hashedPassword = Hash::make($command->password);

        // 3. Create domain entity
        $user = User::register(
            id: Uuid::uuid4()->toString(),
            name: $command->name,
            email: new EmailAddress($command->email),
            passwordHash: $hashedPassword
        );

        // 4. Persistence
        $this->userRepository->save($user);

        // TODO: here will dispatch domain events
    }
}