<?php

namespace InnoSoft\AuthCore\Application\Permissions\Queries\Handlers;

use Illuminate\Pagination\LengthAwarePaginator;
use InnoSoft\AuthCore\Application\Permissions\Queries\GetPermissionsQuery;
use InnoSoft\AuthCore\Application\Permissions\Queries\PermissionReadModel;
use Spatie\Permission\Models\Permission as SpatiePermission;

class GetPermissionsHandler
{
    public function handle(GetPermissionsQuery $query): LengthAwarePaginator
    {
        $builder = SpatiePermission::query()
            ->select(['id', 'name', 'guard_name', 'created_at'])
            ->orderBy('created_at', 'desc');

        if ($query->search) {
            $builder->where('name', 'like', "%{$query->search}%");
        }

        $paginated = $builder->paginate($query->perPage, ['*'], 'page', $query->page);

        $paginated->through(function ($permission) {
            return new PermissionReadModel(
                id: (string)$permission->id,
                name: $permission->name,
                guardName: $permission->guard_name,
                createdAt: $permission->created_at->toIso8601String()
            );
        });

        return $paginated;
    }
}
