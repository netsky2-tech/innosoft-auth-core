<?php

namespace InnoSoft\AuthCore\Application\Users\Handlers;

use Exception;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\DB;
use InnoSoft\AuthCore\Application\Users\Commands\CreateUserCommand;
use InnoSoft\AuthCore\Domain\Shared\DomainEventBus;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Domain\Users\Exceptions\UserAlreadyExistsException;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use InnoSoft\AuthCore\Domain\Users\ValueObjects\EmailAddress;
use Ramsey\Uuid\Uuid;

readonly class CreateUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private Hasher $hasher,
        private DomainEventBus $domainEventBus
    ){}

    /**
     * @throws UserAlreadyExistsException
     * @throws Exception
     */
    public function __invoke(CreateUserCommand $command): User
    {
        // Domain Validation
        if ($this->userRepository->existsByEmail($command->email)) {
            throw new UserAlreadyExistsException($command->email);
        }

        $email = new EmailAddress($command->email);

        $hashedPassword = $this->hasher->make($command->password);

        return DB::transaction(function () use ($email, $hashedPassword, $command) {
            $user = User::register(
                id: Uuid::uuid4()->toString(),
                name: $command->name,
                email: $email,
                passwordHash: $hashedPassword,
            );
            $this->userRepository->save($user);

            $this->domainEventBus->publish(...$user->pullDomainEvents());

            return $user;
        });
    }
}