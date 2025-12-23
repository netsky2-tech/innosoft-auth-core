<?php

namespace InnoSoft\AuthCore\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use InnoSoft\AuthCore\Domain\Users\Events\UserEmailChanged;
use Illuminate\Support\Facades\Mail;


class SendEmailChangeAlerts implements ShouldQueue
{
    use InteractsWithQueue;
    public function handle(UserEmailChanged $event): void
    {
        // 1. Enviar correo de verificación al NUEVO email
        // El usuario debe confirmar que posee este correo.
        Mail::to($event->email())->send(
            new VerifyNewEmailMailable($event->user(), $event->email())
        );

        // 2. Enviar alerta de seguridad al ANTIGUO email
        // "Alguien cambió tu correo. Si no fuiste tú, haz click aquí para bloquear la cuenta".
        Mail::to($event->oldEmail())->send(
            new EmailChangedSecurityAlertMailable($event->user(), $event->oldEmail())
        );
    }
}