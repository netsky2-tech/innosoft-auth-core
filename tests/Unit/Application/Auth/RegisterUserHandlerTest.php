<?php

use Illuminate\Support\Facades\Hash;
use InnoSoft\AuthCore\Application\Auth\Commands\RegisterUserCommand;
use InnoSoft\AuthCore\Application\Auth\Handlers\RegisterUserHandler;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;

test('it handles user registration successfully', function () {
    // 1. Arrange
    Hash::shouldReceive('make')->with('secret123')->andReturn('hashed_secret');

    // Mock repository
    $repository = Mockery::mock(UserRepository::class);
    $repository->shouldReceive('findByEmail')->andReturn(null); // user doesn't exists
    $repository->shouldReceive('save')
        ->once()
        ->with(Mockery::on(function ($user) {
            return $user instanceof User
                && $user->getEmail()->getValue() === 'new@innosoft.com'
                && $user->getPasswordHash() === 'hashed_secret';
        }));

    // 2. Act
    $command = new RegisterUserCommand(
        name: 'John Doe',
        email: 'new@innosoft.com',
        password: 'secret123'
    );

    $handler = new RegisterUserHandler($repository);
    $handler->handle($command);

    // 3. Assert
    expect(true)->toBeTrue();
});

test('it throws exception if user already exists', function () {
    // Arrange
    $repository = Mockery::mock(UserRepository::class);
    // Simualte that findByEmail return something (not null)
    $repository->shouldReceive('findByEmail')->andReturn(Mockery::mock(User::class));

    $command = new RegisterUserCommand('John', 'exists@innosoft.com', 'secret');
    $handler = new RegisterUserHandler($repository);

    // Act & Assert
    // We need create a specific domain exception
    expect(fn() => $handler->handle($command))
        ->toThrow(\InnoSoft\AuthCore\Domain\Users\Exceptions\UserAlreadyExistsException::class);
});