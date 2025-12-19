<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use InnoSoft\AuthCore\Domain\Users\ValueObjects\EmailAddress;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User as EloquentUser;
use InnoSoft\AuthCore\Infrastructure\Persistence\EloquentUserRepository;

uses(RefreshDatabase::class);

test(/**
 * @throws \Illuminate\Contracts\Container\BindingResolutionException
 */ 'it saves a domain user to the database using eloquent repository', function () {
    // 1. Arrange: Crear un Usuario de Dominio
    $domainUser = User::register(
        id: 'uuid-test-1234',
        name: 'Jane Doe',
        email: new EmailAddress('jane@innosoft.com'),
        passwordHash: 'hashed_secret_123'
    );

    // 2. Act: Guardar usando el repositorio
    $repository = $this->app->make(UserRepository::class);
    $repository->save($domainUser);

    // 3. Assert: Verificar que existe en la tabla 'users' (Mundo Eloquent)
    expect(EloquentUser::count())->toBe(1);

    $record = EloquentUser::first();
    expect($record->id)->toBe('uuid-test-1234')
        ->and($record->email)->toBe('jane@innosoft.com');
});

test('it can retrieve a domain user by email', function () {
    // 1. Arrange: Insertar directo en BD usando Eloquent Factory o create
    EloquentUser::create([
        'id' => 'uuid-existing',
        'name' => 'Existing User',
        'email' => 'exist@innosoft.com',
        'password' => 'secret'
    ]);

    // 2. Act: Buscar con el repositorio
    $repository = $this->app->make(UserRepository::class);
    $foundUser = $repository->findByEmail('exist@innosoft.com');

    // 3. Assert: Debemos recibir una Entidad de Dominio, no un Modelo Eloquent
    expect($foundUser)->toBeInstanceOf(User::class)
        ->and($foundUser->getId())->toBe('uuid-existing')
        ->and($foundUser->getEmail()->getValue())->toBe('exist@innosoft.com');
});