<?php

namespace InnoSoft\AuthCore\Domain\Users\ValueObjects;

use InnoSoft\AuthCore\Domain\Users\Exceptions\InvalidEmailException;
final class EmailAddress
{
    private string $value;
    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = strtolower(trim($value));
    }
    private function validate(string $email): void
    {
        if (empty($email)) {
            throw new InvalidEmailException('Email cannot be empty');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException("Invalid email format: {$email}");
        }
    }
    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(EmailAddress $emailAddress): bool
    {
        return $this->value === $emailAddress->getValue();
    }
}