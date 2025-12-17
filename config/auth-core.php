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
];