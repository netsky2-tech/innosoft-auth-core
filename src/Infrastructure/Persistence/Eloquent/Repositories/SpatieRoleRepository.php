<?php

namespace InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\Repositories;


use InnoSoft\AuthCore\Domain\Roles\RoleDTO;
use InnoSoft\AuthCore\Domain\Roles\RoleRepository;
use Spatie\Permission\Models\Role as SpatieRole;

class SpatieRoleRepository implements RoleRepository
{

    public function save(RoleDTO $roleDto): void
    {
        $role = SpatieRole::create([
            'name' => $roleDto->name,
            'guard_name' => $roleDto->guardName,
        ]);

        if(!empty($roleDto->permissions)) {
            $role->syncPermissions($roleDto->permissions);
        }
    }

    public function findById(string $id): \Spatie\Permission\Contracts\Role|SpatieRole|null
    {
        return SpatieRole::find($id);
    }

    public function findByName(string $name, string $guardName): \Spatie\Permission\Contracts\Role|SpatieRole|null
    {
        return SpatieRole::where('name', $name)
            ->where('guard_name', $guardName)
            ->first();
    }

    public function delete(string $id): ?bool
    {
        $role = SpatieRole::find($id);
        return $role ? $role->delete() : false;
    }

    public function syncPermissions(string $roleName, array $permissionNames, string $guardName): void
    {
        $role = $this->findByName($roleName, $guardName);
        if ($role) {
            $role->syncPermissions($permissionNames);
        }
    }

    public function exists(string $name, string $guardName): bool
    {
        return SpatieRole::where('name', $name)
            ->where('guard_name', $guardName)
            ->exists();
    }
}
