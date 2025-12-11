<?php

use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Domain\Users\ValueObjects\EmailAddress;
use InnoSoft\AuthCore\Domain\Users\Events\UserRegistered;

test('a user can be registered via factory method', function () {
    $email = new EmailAddress('newuser@innosoft.com');

    // using static method default to encapsulate
    $user = User::register(
        id: 'uuid-1234',
        name: 'John Doe',
        email: $email,
        passwordHash: 'hashed_secret'
    );

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->getEmail()->getValue())->toBe('newuser@innosoft.com');

    // Verifying if the event dispatch
    $events = $user->pullDomainEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(UserRegistered::class)
        ->and($events[0]->email())->toBe('newuser@innosoft.com');
});