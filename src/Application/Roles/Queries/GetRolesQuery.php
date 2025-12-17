<?php

namespace InnoSoft\AuthCore\Application\Roles\Queries;

class GetRolesQuery
{
    public function __construct(
        public ?string $search = null,
        public int $page = 1,
        public int $perPage = 15
    ) {}
}