<?php

namespace InnoSoft\AuthCore\Application\Auth\Commands\Handlers;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use InnoSoft\AuthCore\Application\Auth\Commands\ResetPasswordCommand;
use InnoSoft\AuthCore\Domain\Auth\Services\PasswordTokenService;
use InnoSoft\AuthCore\Domain\Users\Events\PasswordResetCompleted;
use InnoSoft\AuthCore\Domain\Users\Events\TwoFactorEnabled;
use InnoSoft\AuthCore\Domain\Users\UserRepository;

final readonly class ResetPasswordHandler
{
    public function __construct(
        private UserRepository       $userRepository,
        private PasswordTokenService $tokenService,
        private Hasher $hasher,
        private Dispatcher $dispatcher,
    ) {}

    /**
     * @throws Exception
     */
    public function handle(ResetPasswordCommand $command): void
    {
        $user = $this->userRepository->findByEmail($command->email);

        if (!$user) {
            // Timing attack prevention: simulate work or generic error
            throw new Exception('Invalid email or token.');
        }

        if (!$this->tokenService->validateToken($user, $command->token)) {
            throw new Exception('Invalid email or token.');
        }

        // Update Password
        $user->updatePassword($this->hasher->make($command->password));

        // save changes
        $this->userRepository->save($user);

        // Invalidate token used
        $this->tokenService->deleteToken($user);

        // Event
        $this->dispatcher->dispatch(new PasswordResetCompleted($user->getId(), $user->getEmail()));
    }
}