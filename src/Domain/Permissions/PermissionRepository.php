<?php

namespace InnoSoft\AuthCore\Domain\Permissions;

use Spatie\Permission\Models\Permission as SpatiePermission;

interface PermissionRepository
{
    public function save(PermissionDTO $permissionDto): void;
    public function findById(string $id): \Spatie\Permission\Contracts\Permission|SpatiePermission|null;
    public function findByName(string $name, string $guardName): \Spatie\Permission\Contracts\Permission|SpatiePermission|null;
    public function delete(string $id): ?bool;
    public function exists(string $name, string $guardName): bool;
}
