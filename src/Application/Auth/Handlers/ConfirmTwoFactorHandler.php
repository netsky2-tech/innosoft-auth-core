<?php

namespace InnoSoft\AuthCore\Application\Auth\Handlers;

use Illuminate\Validation\ValidationException;
use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorProvider;
use InnoSoft\AuthCore\Domain\Users\Events\TwoFactorEnabled;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;

final class ConfirmTwoFactorHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private TwoFactorProvider $provider
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(string $userId, string $code): array
    {
        $user = $this->userRepository->findById($userId);

        if (!$user->getTwoFactorSecret()) {
            throw ValidationException::withMessages(['code' => 'Two factor authentication has not been enabled.']);
        }

        // 1. Validating TOTP
        if (!$this->provider->verify($user->getTwoFactorSecret(), $code)) {
            throw ValidationException::withMessages(['code' => 'The provided two factor authentication code was invalid.']);
        }

        // 2. Confirm entity
        $user->confirmTwoFactor();

        // 3. Generate recovery codes (Backup codes)
        $recoveryCodes = $this->provider->generateRecoveryCodes();

        // 4. Save the recovery codes
        $user->setRecoveryCodes($recoveryCodes);

        $this->userRepository->save($user);

        // 5. Event
        event(new TwoFactorEnabled($userId));
        return ['recovery_codes' => $recoveryCodes];
    }
}