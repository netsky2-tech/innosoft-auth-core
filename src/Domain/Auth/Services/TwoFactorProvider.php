<?php

namespace InnoSoft\AuthCore\Domain\Auth\Services;

interface TwoFactorProvider
{
    public function generateSecretKey(): string;
    public function verify(string $secret, string $code): bool;
    public function generateRecoveryCodes(): array;
    public function qrCodeUrl(string $companyName, string $holderEmail, string $secret): string;

}