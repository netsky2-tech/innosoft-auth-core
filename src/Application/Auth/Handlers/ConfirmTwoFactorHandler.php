<?php

namespace InnoSoft\AuthCore\Application\Auth\Handlers;

use Illuminate\Validation\ValidationException;
use InnoSoft\AuthCore\Application\Auth\Commands\ConfirmTwoFactorCommand;
use InnoSoft\AuthCore\Domain\Auth\Exceptions\InvalidTwoFactorCodeException;
use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorProvider;
use InnoSoft\AuthCore\Domain\Shared\DomainEventBus;
use InnoSoft\AuthCore\Domain\Users\Events\TwoFactorEnrollmentConfirmed;
use InnoSoft\AuthCore\Domain\Users\Events\TwoFactorEnrollmentInitiated;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;

final readonly class ConfirmTwoFactorHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private TwoFactorProvider $provider,
        private DomainEventBus   $domainEventBus
    ) {}

    /**
     * @throws ValidationException
     * @throws InvalidTwoFactorCodeException
     */
    public function handle(ConfirmTwoFactorCommand $command): array
    {
        $user = $this->userRepository->findById($command->userId);

        if (!$user->getTwoFactorSecret()) {
            throw new \DomainException('Two factor authentication has not been initialized.');
        }

        // 1. Validating TOTP
        if (!$this->provider->verify($user->getTwoFactorSecret(), $command->code)) {
            throw new InvalidTwoFactorCodeException();
        }

        // 2. Generate recovery codes (Backup codes)
        $recoveryCodes = $this->provider->generateRecoveryCodes();

        // 3. Update domain state
        $user->completeTwoFactorEnrollment($recoveryCodes);

        // 4. Persist
        $this->userRepository->save($user);

        // 5. Event
        $this->domainEventBus->publish(...$user->pullDomainEvents());

        return ['recovery_codes' => $recoveryCodes];
    }
}