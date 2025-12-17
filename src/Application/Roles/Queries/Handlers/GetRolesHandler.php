<?php

namespace InnoSoft\AuthCore\Application\Roles\Queries\Handlers;

use Illuminate\Pagination\LengthAwarePaginator;
use InnoSoft\AuthCore\Application\Roles\Queries\GetRolesQuery;
use InnoSoft\AuthCore\Application\Roles\Queries\RoleReadModel;
use Spatie\Permission\Models\Role as SpatieRole;

class GetRolesHandler
{
    public function handle(GetRolesQuery $query): LengthAwarePaginator
    {
        $builder = SpatieRole::query()
            ->with('permissions')
            ->select(['id', 'name', 'guard_name', 'created_at'])
            ->orderBy('created_at', 'desc');

        if ($query->search) {
            $builder->where('name', 'like', "%{$query->search}%");
        }

        $paginated = $builder->paginate($query->perPage, ['*'], 'page', $query->page);


        $paginated->through(function ($role) {
            return new RoleReadModel(
                id: (string)$role->id,
                name: $role->name,
                guardName: $role->guard_name,
                permissions: $role->permissions->pluck('name')->toArray(),
                createdAt: $role->created_at->toIso8601String()
            );
        });

        return $paginated;
    }
}