<?php

namespace InnoSoft\AuthCore\Domain\Roles;


use Spatie\Permission\Models\Role as SpatieRole;

interface RoleRepository
{
    public function save(RoleDTO $roleDto): void;
    public function findById(string $id): \Spatie\Permission\Contracts\Role|SpatieRole;
    public function delete(string $id): ?bool;
    public function syncPermissions(string $roleName, array $permissionNames, string $guardName): void;
    public function exists(string $name, string $guardName): bool;
}