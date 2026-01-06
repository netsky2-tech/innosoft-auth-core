<?php

namespace InnoSoft\AuthCore\Application\Auth\Handlers;

use Illuminate\Support\Facades\DB;
use InnoSoft\AuthCore\Application\Auth\Commands\EnableTwoFactorCommand;
use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorProvider;
use InnoSoft\AuthCore\Domain\Shared\DomainEventBus;
use InnoSoft\AuthCore\Domain\Users\Exceptions\UserNotFoundException;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use Illuminate\Contracts\Encryption\Encrypter;

readonly class EnableTwoFactorHandler
{
    public function __construct(
        private UserRepository    $userRepository,
        private TwoFactorProvider $provider,
        private Encrypter       $encrypter,
        private DomainEventBus   $domainEventBus
    ) {}

    /**
     * @throws UserNotFoundException
     * @throws \Throwable
     */
    public function __invoke(EnableTwoFactorCommand $command): array
    {
        $user = $this->userRepository->findById($command->userId);

        if (!$user) {
           throw new UserNotFoundException("User with id {$command->userId} not found.");
        }

        return DB::transaction(function () use ($command, $user) {
            // 1. Generate the secret
            $plainSecret = $this->provider->generateSecretKey();

            // 2. Encrypt the key
            $encryptedKey = $this->encrypter->encrypt($plainSecret);

            // 3. Update the domain state
            $user->initiateTwoFActorEnrollment($encryptedKey);

            // 4. Persistent
            $this->userRepository->save($user);

            // 5. Publish event
            $this->domainEventBus->publish(...$user->pullDomainEvents());

            // 6. Generate data for the frontend
            return [
                'secret' => $plainSecret,
                'qr_code_url' => $this->provider->qrCodeUrl(
                    config('app.name', 'InnoSoft'),
                    $user->getEmail()->getValue(),
                    $plainSecret
                )
            ];
        });

    }
}