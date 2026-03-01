<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class VerifyEmailCodeController extends Controller
{
    /**
     * Validate the e-mail verification code for the authenticated user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('legacy.index', absolute: false).'?verified=1');
        }

        if (! $user->email_verification_code_hash || ! $user->email_verification_code_expires_at) {
            return back()->withErrors([
                'code' => 'Nenhum codigo ativo. Solicite um novo codigo de verificacao.',
            ]);
        }

        if ($user->email_verification_code_attempts >= 5) {
            return back()->withErrors([
                'code' => 'Limite de tentativas excedido. Solicite um novo codigo.',
            ]);
        }

        if ($user->email_verification_code_expires_at->isPast()) {
            return back()->withErrors([
                'code' => 'Codigo expirado. Solicite um novo codigo.',
            ]);
        }

        if (! Hash::check($validated['code'], $user->email_verification_code_hash)) {
            $user->increment('email_verification_code_attempts');

            return back()->withErrors([
                'code' => 'Codigo invalido. Confira e tente novamente.',
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $user->clearEmailVerificationCode();

        return redirect()->intended(route('legacy.index', absolute: false).'?verified=1');
    }
}
