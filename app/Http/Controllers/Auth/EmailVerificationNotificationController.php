<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('legacy.index', absolute: false));
        }

        $sentAt = $user->email_verification_code_sent_at;
        if ($sentAt && $sentAt->diffInSeconds(now()) < 60) {
            $secondsLeft = 60 - $sentAt->diffInSeconds(now());

            return back()->with('status', "Aguarde {$secondsLeft}s para reenviar um novo codigo.");
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'Novo codigo de verificacao enviado com sucesso.');
    }
}
