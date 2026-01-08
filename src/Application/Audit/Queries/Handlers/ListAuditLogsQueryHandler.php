<?php

namespace InnoSoft\AuthCore\Application\Audit\Queries\Handlers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InnoSoft\AuthCore\Application\Audit\DTOs\AuditLogView;
use InnoSoft\AuthCore\Application\Audit\Queries\ListAuditLogsQuery;
use Spatie\Activitylog\Models\Activity;

class ListAuditLogsQueryHandler
{
    public function handle(ListAuditLogsQuery $query): LengthAwarePaginator
    {
        $builder = Activity::query()->with('causer');

        if ($query->userId) {
            $builder->where('causer_id', $query->userId);
        }

        if ($query->subjectId) {
            $builder->where('subject_id', $query->subjectId);
        }

        if ($query->subjectType) {
            $builder->where('subject_type', $query->subjectType);
        }

        if ($query->event) {
            $builder->where('event', $query->event);
        }

        if ($query->dateFrom) {
            $builder->whereDate('created_at', '>=', $query->dateFrom);
        }

        if ($query->dateTo) {
            $builder->whereDate('created_at', '<=', $query->dateTo);
        }

        $builder->latest();

        $paginator = $builder->paginate(
            perPage: $query->perPage,
            page: $query->page
        );

        $paginator->getCollection()->transform(function (Activity $activity) {
            return AuditLogView::fromModel($activity);
        });

        return $paginator;
    }
}
