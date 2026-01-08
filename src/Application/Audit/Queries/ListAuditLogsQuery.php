<?php

namespace InnoSoft\AuthCore\Application\Audit\Queries;

final readonly class ListAuditLogsQuery
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 15,
        public ?string $userId = null,
        public ?string $subjectId = null,
        public ?string $subjectType = null,
        public ?string $event = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null
    ) {}
}
