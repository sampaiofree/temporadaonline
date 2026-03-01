<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code
    ) {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the verification code mail message.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirme seu e-mail - Legacy XI')
            ->line('Voce recebeu este e-mail porque criou uma conta no Legacy XI.')
            ->line('Seu codigo de verificacao e: '.$this->code)
            ->line('O codigo expira em 15 minutos.')
            ->line('Se voce nao criou esta conta, ignore esta mensagem.');
    }
}
