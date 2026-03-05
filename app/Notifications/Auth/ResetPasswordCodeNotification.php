<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code
    ) {
    }

    public function code(): string
    {
        return $this->code;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the password reset code mail message.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Redefinicao de senha - Legacy XI')
            ->line('Recebemos uma solicitacao para redefinir a senha da sua conta.')
            ->line('Seu codigo de redefinicao e: '.$this->code)
            ->line('O codigo expira em 15 minutos.')
            ->line('Se voce nao solicitou a redefinicao, ignore este e-mail.');
    }
}
