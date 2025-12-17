<?php

namespace InnoSoft\AuthCore\Application\Users\Queries;

final readonly class ListUsersQuery
{
    public function __construct(
        public int     $page = 1,
        public int     $perPage = 15,
        public ?string $search = null,
        public ?string $sortBy = 'name',
    ) {}
}