<?php

namespace InnoSoft\AuthCore\Application\Users\Queries\Handlers;

use Illuminate\Pagination\LengthAwarePaginator;
use InnoSoft\AuthCore\Application\Users\DTOs\UserView;
use InnoSoft\AuthCore\Application\Users\Queries\ListUsersQuery;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User;

final readonly class ListUsersQueryHandler
{
    public function __construct(
        private UserRepository $userRepository
    ) {}
    public function __invoke(ListUsersQuery $query): LengthAwarePaginator
    {
        $paginator = $this->userRepository->search(
            page: $query->page,
            perPage: $query->perPage,
            term: $query->search,
            sortBy: $query->sortBy ?? 'created_at'
        );

        $paginator->through(fn ($userModel) => UserView::fromDomain($userModel));

        return $paginator;
    }
}