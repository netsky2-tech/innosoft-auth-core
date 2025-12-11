<?php

use InnoSoft\AuthCore\Domain\Users\ValueObjects\EmailAddress;
use InnoSoft\AuthCore\Domain\Users\Exceptions\InvalidEmailException;

test('it creates a valid email address', function () {
    $email = new EmailAddress('architect@innosoft.com');
    expect($email->getValue())->toBe('architect@innosoft.com')
        ->and((string) $email)->toBe('architect@innosoft.com');
});

test('it normalizes the email address to lowercase', function () {
    $email = new EmailAddress('ARCHITECT@InnoSoft.com');
    expect($email->getValue())->toBe('architect@innosoft.com');
});

test('it throws exception for invalid email format', function () {
    new EmailAddress('invalid-email');
})->throws(InvalidEmailException::class);

test('it throws exception for empty email', function () {
    new EmailAddress('');
})->throws(InvalidEmailException::class);