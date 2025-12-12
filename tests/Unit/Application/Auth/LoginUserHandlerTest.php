<?php

use InnoSoft\AuthCore\Application\Auth\Commands\LoginUserCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\Handlers\LoginUserHandler;
use InnoSoft\AuthCore\Domain\Users\UserRepository;
use InnoSoft\AuthCore\Domain\Auth\Services\TokenIssuer;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Domain\Users\ValueObjects\EmailAddress;
use InnoSoft\AuthCore\Domain\Users\Exceptions\InvalidCredentialsException;
use Illuminate\Support\Facades\Hash;

test('it logs in a user with valid credentials and returns a token', function () {
    // 1. Arrange
    $user = User::register(
        'uuid-1', 'John', new EmailAddress('john@innosoft.com'), 'hashed_secret'
    );

    $repo = Mockery::mock(UserRepository::class);
    $repo->shouldReceive('findByEmail')->with('john@innosoft.com')->andReturn($user);

    Hash::shouldReceive('check')->with('secret', 'hashed_secret')->andReturn(true);

    $tokenIssuer = Mockery::mock(TokenIssuer::class);
    $tokenIssuer->shouldReceive('issue')->with($user, 'device-test')->andReturn('valid-token-123');

    // 2. Act
    $command = new LoginUserCommand('john@innosoft.com', 'secret', 'device-test');
    $handler = new LoginUserHandler($repo, $tokenIssuer);
    $result = $handler->handle($command);

    // 3. Assert
    expect($result)->toBeArray()
        ->and($result['access_token'])->toBe('valid-token-123');
});

test('it throws invalid credentials exception if user not found', function () {
    $repo = Mockery::mock(UserRepository::class);
    $repo->shouldReceive('findByEmail')->andReturn(null);

    $tokenIssuer = Mockery::mock(TokenIssuer::class);

    $command = new LoginUserCommand('ghost@innosoft.com', 'secret');
    $handler = new LoginUserHandler($repo, $tokenIssuer);

    expect(fn() => $handler->handle($command))
        ->toThrow(InvalidCredentialsException::class);
});

test('it throws invalid credentials exception if password does not match', function () {
    $user = User::register(
        'uuid-1', 'John', new EmailAddress('john@innosoft.com'), 'hashed_real_secret'
    );

    $repo = Mockery::mock(UserRepository::class);
    $repo->shouldReceive('findByEmail')->andReturn($user);

    Hash::shouldReceive('check')->andReturn(false);

    $tokenIssuer = Mockery::mock(TokenIssuer::class);

    $command = new LoginUserCommand('john@innosoft.com', 'wrong-pass');
    $handler = new LoginUserHandler($repo, $tokenIssuer);

    expect(fn() => $handler->handle($command))
        ->toThrow(InvalidCredentialsException::class);
});