<?php

namespace InnoSoft\AuthCore\Application\Auth\Handlers;

use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorProvider;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;

final readonly class EnableTwoFactorHandler
{
    public function __construct(
        private UserRepository    $userRepository,
        private TwoFactorProvider $provider
    ) {}

    public function handle(string $userId): array
    {
        $user = $this->userRepository->findById($userId);

        // 1. Generate the secret
        $secret = $this->provider->generateSecretKey();

        $user->enableTwoFactor($secret);
        $this->userRepository->save($user);

        // 3. Generate data for the frontend
        return [
            'secret' => $secret,
            'qr_code_url' => $this->provider->qrCodeUrl(
                config('app.name', 'InnoSoft'),
                $user->getEmail()->getValue(),
                $secret
            )
        ];
    }
}