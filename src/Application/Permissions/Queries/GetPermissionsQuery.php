<?php

namespace InnoSoft\AuthCore\Application\Permissions\Queries;

class GetPermissionsQuery
{
    public function __construct(
        public ?string $search = null,
        public int $page = 1,
        public int $perPage = 15
    ) {}
}
