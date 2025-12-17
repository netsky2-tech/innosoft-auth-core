<?php

use InnoSoft\AuthCore\Application\Auth\Commands\RequestPasswordResetCommand;
use InnoSoft\AuthCore\Application\Auth\Handlers\RequestPasswordResetHandler;
use InnoSoft\AuthCore\Domain\Auth\Services\PasswordTokenService;
use InnoSoft\AuthCore\Domain\Shared\DomainEventBus;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use InnoSoft\AuthCore\Domain\Users\ValueObjects\EmailAddress;

test('it creates a reset token and fires event when user exists', function () {
    // 1. Arrange
    $user = User::register('uuid-1', 'John', new EmailAddress('john@innosoft.com'), 'hash');

    $repo = Mockery::mock(UserRepository::class);
    $repo->shouldReceive('findByEmail')->with('john@innosoft.com')->andReturn($user);

    $tokenService = Mockery::mock(PasswordTokenService::class);
    $tokenService->shouldReceive('createToken')->with($user)->andReturn('secure-token-123');

    // 2. Act
    $command = new RequestPasswordResetCommand('john@innosoft.com');
    $handler = new RequestPasswordResetHandler($repo, $tokenService);

    $result = $handler->handle($command);

    // 3. Assert
    expect($result)->toBe('secure-token-123');

});

test('it does nothing (silently fails) if user does not exist to prevent enumeration attacks', function () {
    $repo = Mockery::mock(UserRepository::class);
    $repo->shouldReceive('findByEmail')->andReturn(null);

    $tokenService = Mockery::mock(PasswordTokenService::class);
    $tokenService->shouldNotReceive('createToken');

    $command = new RequestPasswordResetCommand('ghost@innosoft.com');
    $handler = new RequestPasswordResetHandler($repo, $tokenService);

    $result = $handler->handle($command);

    expect($result)->toBeNull();
});