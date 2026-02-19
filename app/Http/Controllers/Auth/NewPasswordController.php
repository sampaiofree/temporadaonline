<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('legacy.auth.reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'token.required' => 'Token de redefinicao ausente.',
            'email.required' => 'Informe um email.',
            'email.email' => 'Informe um email valido.',
            'password.required' => 'Informe uma nova senha.',
            'password.confirmed' => 'A confirmacao da senha nao confere.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return redirect()
                ->route('legacy.login')
                ->with('status', 'Senha redefinida com sucesso. Faca login com a nova senha.');
        }

        $message = match ($status) {
            Password::INVALID_TOKEN => 'Link de redefinicao invalido ou expirado.',
            Password::RESET_THROTTLED => 'Aguarde alguns minutos antes de tentar novamente.',
            default => 'Nao foi possivel redefinir a senha. Confira os dados e tente novamente.',
        };

        throw ValidationException::withMessages([
            'email' => [$message],
        ]);
    }
}
