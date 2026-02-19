<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    /**
     * Build the password reset notification mail message for the given URL.
     *
     * @param  string  $url
     */
    protected function buildMailMessage($url): MailMessage
    {
        $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Redefinicao de senha - Legacy XI')
            ->line('Recebemos uma solicitacao para redefinir a senha da sua conta.')
            ->action('Redefinir senha', $url)
            ->line("Este link expira em {$expire} minutos.")
            ->line('Se voce nao solicitou a redefinicao, ignore este e-mail.');
    }
}

