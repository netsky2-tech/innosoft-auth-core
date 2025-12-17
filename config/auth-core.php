<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    | The eloquent class that will act as user must implement de package interfaces.
    */
    'user_model' => \InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User::class,

    /*
    |--------------------------------------------------------------------------
    | Token Expiration
    |--------------------------------------------------------------------------
    | Time to expire of tokens (API).
    */
    'token_expiration' => 60 * 24, // 24 hours

    /*
    |--------------------------------------------------------------------------
    | Routes prefix API
    |--------------------------------------------------------------------------
    */
    'prefix' => 'api/auth',

    /*
    |--------------------------------------------------------------------------
    | user table name
    |--------------------------------------------------------------------------
    | Flexibility for legacy integration
    */
    'users_table_name' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Super Admin Role
    |--------------------------------------------------------------------------
    | This role will have access to the entire app
    */
    'super_admin_role' => 'SuperAdmin',

    /*
    |--------------------------------------------------------------------------
    | Roles and Permissions Structure
    |--------------------------------------------------------------------------
    | Define here the roles and permissions
    | Seeder read this to hydrate the database
    */
    'roles_structure' => [
        'Manager' => [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'reports.view',
        ],
        'Editor' => [
            'posts.create',
            'posts.update',
            'media.upload',
        ],
        'Viewer' => [
            'posts.view',
            'users.view_basic',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Single Permits (Optional)
    |--------------------------------------------------------------------------
    | Permissions that exist in the system but are not assigned to roles by default
    */
    'permissions_map' => [
        'system.maintenance',
    ],
];