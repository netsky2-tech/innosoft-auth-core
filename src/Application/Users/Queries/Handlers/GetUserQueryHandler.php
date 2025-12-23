<?php

namespace InnoSoft\AuthCore\Application\Users\Queries\Handlers;

use InnoSoft\AuthCore\Application\Users\DTOs\UserView;
use InnoSoft\AuthCore\Application\Users\Queries\GetUserQuery;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Domain\Users\Exceptions\UserNotFoundException;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;


final readonly class GetUserQueryHandler
{

    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * @throws UserNotFoundException
     */
    public function __invoke(GetUserQuery $query): UserView
    {
        $user = $this->userRepository->findById($query->userId);

        if (!$user) {
            throw new UserNotFoundException("User with ID {$query->userId} not found.");
        }

        return UserView::fromDomain($user);
    }
}