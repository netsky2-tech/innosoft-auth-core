<?php

namespace InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'users';

    // disable auto increment because we use UUID
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}