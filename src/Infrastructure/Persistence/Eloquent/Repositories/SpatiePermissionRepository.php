<?php

namespace InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\Repositories;

use InnoSoft\AuthCore\Domain\Permissions\PermissionDTO;
use InnoSoft\AuthCore\Domain\Permissions\PermissionRepository;
use Spatie\Permission\Models\Permission as SpatiePermission;

class SpatiePermissionRepository implements PermissionRepository
{
    public function save(PermissionDTO $permissionDto): void
    {
        SpatiePermission::create([
            'name' => $permissionDto->name,
            'guard_name' => $permissionDto->guardName,
        ]);
    }

    public function findById(string $id): \Spatie\Permission\Contracts\Permission|SpatiePermission|null
    {
        return SpatiePermission::find($id);
    }

    public function findByName(string $name, string $guardName): \Spatie\Permission\Contracts\Permission|SpatiePermission|null
    {
        return SpatiePermission::where('name', $name)
            ->where('guard_name', $guardName)
            ->first();
    }

    public function delete(string $id): ?bool
    {
        $permission = SpatiePermission::find($id);
        return $permission ? $permission->delete() : false;
    }

    public function exists(string $name, string $guardName): bool
    {
        return SpatiePermission::where('name', $name)
            ->where('guard_name', $guardName)
            ->exists();
    }
}
