<?php

namespace InnoSoft\AuthCore\Infrastructure\Persistence;


use InnoSoft\AuthCore\Domain\Roles\RoleDTO;
use InnoSoft\AuthCore\Domain\Roles\RoleRepository;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Models\Permission;
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

    public function findById(string $id): \Spatie\Permission\Contracts\Role|SpatieRole
    {
        return SpatieRole::findById($id);
    }

    public function delete(string $id): ?bool
    {
        return (new \Spatie\Permission\Models\Role)->delete($id);
    }

    public function syncPermissions(string $roleName, array $permissionNames, string $guardName): void
    {
        $role = SpatieRole::findByName($roleName, $guardName);
        $role->syncPermissions($permissionNames);
    }

    public function exists(string $name, string $guardName): bool
    {
        return SpatieRole::where($name, $name)
            ->where('guard_name', $guardName)
            ->exists();
    }
}