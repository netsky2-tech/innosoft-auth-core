<?php

use Illuminate\Support\Facades\Hash;
use InnoSoft\AuthCore\Application\Auth\Commands\LoginUserCommand;
use InnoSoft\AuthCore\Application\Auth\Handlers\LoginUserHandler;
use InnoSoft\AuthCore\Domain\Auth\Exceptions\TwoFactorRequiredException;
use InnoSoft\AuthCore\Domain\Auth\Services\TokenIssuer;
use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Domain\Users\Exceptions\InvalidCredentialsException;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use InnoSoft\AuthCore\Domain\Users\ValueObjects\EmailAddress;

test(/**
 * @throws TwoFactorRequiredException
 * @throws InvalidCredentialsException
 */ 'it logs in a user with valid credentials and returns a token', function () {
    // 1. Arrange

    // --- Configuración de datos ---
    $userId = 'uuid-1';
    $emailAddress = new EmailAddress('john@innosoft.com');
    $password = 'secret';
    $passwordHash = 'hashed_secret';
    $deviceName = 'device-test';

    // --- Mocks de Objetos del Dominio ---

    // 1.1 Simular el Modelo de Dominio (User)
    $domainUserMock = Mockery::mock(User::class);
    $domainUserMock->shouldReceive('getId')->andReturn($userId);
    $domainUserMock->shouldReceive('getPasswordHash')->andReturn($passwordHash);
    $domainUserMock->shouldReceive('hasTwoFactorEnabled')->andReturn(false);
    $domainUserMock->shouldReceive('getEmail')->andReturn($emailAddress); // Para el TokenIssuer
    $domainUserMock->shouldReceive('getName')->andReturn('John'); // Si se usa en la respuesta

    // 1.2 Simular el Modelo Eloquent/Authenticatable (Para el evento Login)
    // Usamos Authenticatable para ser genéricos, ya que EloquentUser implementa esta interfaz.
    $eloquentUserMock = Mockery::mock(\InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User::class);

    $eloquentUserMock->shouldReceive('setAttribute')
        ->with('id', $userId)
        ->andSet('id', $userId);
    $eloquentUserMock->shouldReceive('getAttribute')
        ->with('id')
        ->andReturn($userId);

    // Propiedad 'id' requerida por el evento/framework
    $eloquentUserMock->id = $userId;
    // Mockear métodos que Laravel podría llamar
    $eloquentUserMock->shouldReceive('getAuthIdentifier')->andReturn($userId);

    // --- Mocks de Dependencias (El Core) ---

    // 1.3 Mockear Repositorio (UserRepository)
    $repo = Mockery::mock(UserRepository::class);

    // Expectativa 1: findByEmail (para la autenticación)
    $repo->shouldReceive('findByEmail')->with('john@innosoft.com')->andReturn($domainUserMock);

    // 🚨 Expectativa 2: findAuthenticatableById (para obtener el EloquentUser para el evento)
    $repo->shouldReceive('findAuthenticatableById')
        ->once()
        ->with($userId)
        ->andReturn($eloquentUserMock);

    // 1.4 Mockear Hash (Fachada)
    Hash::shouldReceive('check')->with($password, $passwordHash)->andReturn(true);

    // 1.5 Mockear TokenIssuer
    $tokenIssuer = Mockery::mock(TokenIssuer::class);
    $tokenIssuer->shouldReceive('issue')->with($domainUserMock, $deviceName)->andReturn('valid-token-123');

    // --- Mockear el framework (Fachadas y Eventos) ---

    // 🚨 CORRECCIÓN CLAVE: Interceptar la llamada a Event::dispatch
    // Esto reemplaza Event::fake() en tests unitarios puros.
    \Illuminate\Support\Facades\Event::shouldReceive('dispatch')
        ->once()
        ->withArgs(function ($event) use ($eloquentUserMock) {
            return $event instanceof \Illuminate\Auth\Events\Login
                && $event->user === $eloquentUserMock
                && $event->guard === 'sanctum';
        });

    // Mockear el servicio 2FA
    $twoFactorService = Mockery::mock(\InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorChallengeService::class);

    // 2. Act
    $command = new LoginUserCommand('john@innosoft.com', $password, $deviceName);
    $handler = new LoginUserHandler($repo, $tokenIssuer);
    $result = $handler->handle($command);

    // 3. Assert

    // El shouldReceive('dispatch')->once() ya verificó que el evento se disparó.

    expect($result)->toBeArray()
        ->and($result['access_token'])->toBe('valid-token-123')
        ->and($result['user']['id'])->toBe($userId);
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

    expect(/**
     * @throws TwoFactorRequiredException
     * @throws InvalidCredentialsException
     */ fn() => $handler->handle($command))
        ->toThrow(InvalidCredentialsException::class);
});