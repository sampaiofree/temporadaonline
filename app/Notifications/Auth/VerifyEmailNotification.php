<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    /**
     * Build the verify email notification mail message for the given URL.
     *
     * @param  string  $url
     */
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirme seu e-mail - Legacy XI')
            ->line('Voce recebeu este e-mail porque criou uma conta no Legacy XI.')
            ->action('Confirmar e-mail', $url)
            ->line('Se voce nao criou esta conta, ignore esta mensagem.');
    }
}

