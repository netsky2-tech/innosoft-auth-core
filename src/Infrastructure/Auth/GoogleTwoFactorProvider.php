<?php

namespace InnoSoft\AuthCore\Infrastructure\Auth;

use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorProvider;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;

class GoogleTwoFactorProvider implements TwoFactorProvider
{
    private Google2FA $engine;

    public function __construct()
    {
        $this->engine = new Google2FA();
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function generateSecretKey(): string
    {
        return $this->engine->generateSecretKey();
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     */
    public function verify(string $secret, string $code): bool
    {
        return $this->engine->verifyKey($secret, $code);
    }

    public function generateRecoveryCodes(): array
    {
        return array_map(fn() => \Illuminate\Support\Str::random(10) . '-' . \Illuminate\Support\Str::random(10), range(1, 8));
    }

    public function qrCodeUrl(string $companyName, string $holderEmail, string $secret): string
    {
        return $this->engine->getQRCodeUrl($companyName, $holderEmail, $secret);
    }
}