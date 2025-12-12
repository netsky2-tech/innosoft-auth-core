<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    | La clase Eloquent que actuará como usuario. Debe implementar las interfaces
    | del paquete. The eloquent class that will act as user must implement de package interfaces.
    */
    'user_model' => \InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User::class,

    /*
    |--------------------------------------------------------------------------
    | Token Expiration
    |--------------------------------------------------------------------------
    | Time to expire of tokens (API).
    */
    'token_expiration' => 60 * 24, // 24 hours
];