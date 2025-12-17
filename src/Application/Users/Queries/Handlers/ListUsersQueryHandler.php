<?php

namespace InnoSoft\AuthCore\Application\Users\Queries\Handlers;

use InnoSoft\AuthCore\Application\Users\Queries\ListUsersQuery;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User;

class ListUsersQueryHandler
{
    public function __invoke(ListUsersQuery $query): \Illuminate\Pagination\LengthAwarePaginator
    {
        $builder = User::query();

        if ($query->search) {
            $builder->where('name', 'like', "%{$query->search}%")
                ->orWhere('email', 'like', "%{$query->search}%");
        }

        $builder->orderBy($query->sortBy ?? 'name');

        return $builder->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}