<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(Request $request): View
    {
        return view('legacy.auth.forgot-password', [
            'status' => session('status'),
            'email' => old('email', $request->query('email')),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Informe um email.',
            'email.email' => 'Informe um email valido.',
        ]);

        $email = mb_strtolower(trim((string) $validated['email']));
        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $sentAt = $user->password_reset_code_sent_at;
            $canResend = ! $sentAt || $sentAt->diffInSeconds(now()) >= 60;

            if ($canResend) {
                $user->sendPasswordResetCodeNotification();
            }
        }

        return redirect()
            ->route('password.reset', ['email' => $email])
            ->with('status', 'Se o email existir em nossa base, enviaremos um codigo de redefinicao.');
    }
}
