<?php

namespace InnoSoft\AuthCore\Application\Auth\Commands\Handlers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use InnoSoft\AuthCore\Domain\Users\UserRepository;

final readonly class DisableTwoFactorHandler
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(string $userId, string $currentPassword): void
    {
        $user = $this->userRepository->findById($userId);

        // 1. Validate actual password
        if (!Hash::check($currentPassword, $user->getPasswordHash())) {
            throw ValidationException::withMessages([
                'current_password' => 'The provided password does not match your current password.'
            ]);
        }

        // 2. Disable domain
        $user->disableTwoFactor();

        // 3. Persist changes
        $this->userRepository->save($user);
    }
}