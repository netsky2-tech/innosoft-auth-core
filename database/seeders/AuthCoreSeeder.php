<?php

namespace InnoSoft\AuthCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AuthCoreSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Clean spatie cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $config = config('auth-core');
        $structure = $config['roles_structure'] ?? [];
        $superAdminName = $config['super_admin_role'] ?? 'SuperAdmin';
        $guard = 'api';

        // 2. Create SuperAdminRole
        Role::firstOrCreate(['name' => $superAdminName, 'guard_name' => $guard]);

        // 3. Iterate configuration matrix
        foreach ($structure as $roleName => $permissions) {
            // A. Create or search the role
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);

            $permissionsToSync = [];

            foreach ($permissions as $permissionName) {
                // B. Create or search the Permission
                $permission = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => $guard
                ]);

                $permissionsToSync[] = $permission;
            }

            // C. Sync
            $role->syncPermissions($permissionsToSync);
        }

        // 4. Create singles permits
        foreach ($config['permissions_map'] ?? [] as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => $guard]);
        }

        $this->command->info('AuthCore RBAC seeded successfully!');
    }
}