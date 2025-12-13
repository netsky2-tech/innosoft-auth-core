<?php

use InnoSoft\AuthCore\Application\Auth\Commands\ResetPasswordCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\Handlers\ResetPasswordHandler;
use InnoSoft\AuthCore\Domain\Users\UserRepository;
use InnoSoft\AuthCore\Domain\Auth\Services\PasswordTokenService;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Domain\Users\ValueObjects\EmailAddress;
use InnoSoft\AuthCore\Domain\Users\Exceptions\InvalidCredentialsException;

test('it resets password with valid token', function () {
    $user = User::register('uuid-1', 'John', new EmailAddress('john@innosoft.com'), 'old_hash');

    $repo = Mockery::mock(UserRepository::class);
    $repo->shouldReceive('findByEmail')->andReturn($user);
    $repo->shouldReceive('save')->once();

    $hasher = Mockery::mock(\Illuminate\Contracts\Hashing\Hasher::class);
    $hasher->shouldReceive('make')
        ->with('NewSecret123!')
        ->andReturn('fake_hashed_value');

    $tokenService = Mockery::mock(PasswordTokenService::class);
    $tokenService->shouldReceive('validateToken')->with($user, 'valid-token')->andReturn(true);
    $tokenService->shouldReceive('deleteToken')->with($user)->once();

    $command = new ResetPasswordCommand('john@innosoft.com', 'valid-token', 'NewSecret123!');
    $handler = new ResetPasswordHandler($repo, $tokenService, $hasher);

    $handler->handle($command);

    expect(true)->toBeTrue();
});

test('it throws exception with invalid token', function () {
    $user = User::register('uuid-1', 'John', new EmailAddress('john@innosoft.com'), 'hash');

    $repo = Mockery::mock(UserRepository::class);
    $repo->shouldReceive('findByEmail')->andReturn($user);
    $repo->shouldNotReceive('save');

    $tokenService = Mockery::mock(PasswordTokenService::class);
    $tokenService->shouldReceive('validateToken')->andReturn(false);

    $hasher = Mockery::mock(\Illuminate\Contracts\Hashing\Hasher::class);

    $command = new ResetPasswordCommand('john@innosoft.com', 'bad-token', 'pass');
    $handler = new ResetPasswordHandler($repo, $tokenService, $hasher);

    expect(/**
     * @throws Exception
     */ fn() => $handler->handle($command))->toThrow(Exception::class);
});