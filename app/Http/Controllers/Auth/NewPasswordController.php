<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
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
        $email = mb_strtolower(trim((string) $request->input('email', old('email', ''))));
        $verifiedEmail = (string) $request->session()->get('password_reset_verified_email', '');
        $verifiedAt = $request->session()->get('password_reset_verified_at');
        $codeVerified = false;

        if ($email !== '' && $verifiedEmail === $email && is_string($verifiedAt)) {
            try {
                $verifiedAtTime = Carbon::parse($verifiedAt);
                $codeVerified = ! $verifiedAtTime->addMinutes(15)->isPast();
            } catch (\Throwable) {
                $codeVerified = false;
            }
        }

        return view('legacy.auth.reset-password', [
            'email' => $email,
            'status' => session('status'),
            'codeVerified' => $codeVerified,
        ]);
    }

    /**
     * Redirect old link-based flow to the code flow.
     */
    public function createFromToken(Request $request, string $token): RedirectResponse
    {
        $email = mb_strtolower(trim((string) $request->input('email', '')));

        unset($token);

        return redirect()
            ->route('password.request', $email !== '' ? ['email' => $email] : [])
            ->with('status', 'Esse link nao e mais utilizado. Solicite um codigo de redefinicao.');
    }

    /**
     * Validate the reset code and unlock password update.
     */
    public function verifyCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ], [
            'email.required' => 'Informe seu email.',
            'email.email' => 'Informe um email valido.',
            'code.required' => 'Informe o codigo enviado para seu email.',
            'code.digits' => 'O codigo deve ter 6 digitos.',
        ]);

        $email = mb_strtolower(trim((string) $validated['email']));
        $user = User::query()->where('email', $email)->first();

        $invalidCodeResponse = fn () => back()
            ->withInput(['email' => $email])
            ->withErrors([
                'code' => 'Codigo invalido ou expirado. Solicite um novo codigo.',
            ]);

        if (! $user || ! $user->password_reset_code_hash || ! $user->password_reset_code_expires_at) {
            return $invalidCodeResponse();
        }

        if ($user->password_reset_code_attempts >= 5) {
            return back()
                ->withInput(['email' => $email])
                ->withErrors([
                    'code' => 'Limite de tentativas excedido. Solicite um novo codigo.',
                ]);
        }

        if ($user->password_reset_code_expires_at->isPast()) {
            return back()
                ->withInput(['email' => $email])
                ->withErrors([
                    'code' => 'Codigo expirado. Solicite um novo codigo.',
                ]);
        }

        if (! Hash::check((string) $validated['code'], $user->password_reset_code_hash)) {
            $user->increment('password_reset_code_attempts');

            return $invalidCodeResponse();
        }

        $user->markPasswordResetCodeVerified();

        $request->session()->put('password_reset_verified_email', $email);
        $request->session()->put('password_reset_verified_at', now()->toISOString());

        return redirect()
            ->route('password.reset', ['email' => $email])
            ->with('status', 'Codigo confirmado. Agora defina sua nova senha.');
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.required' => 'Informe um email.',
            'email.email' => 'Informe um email valido.',
            'password.required' => 'Informe uma nova senha.',
            'password.confirmed' => 'A confirmacao da senha nao confere.',
        ]);

        $email = mb_strtolower(trim((string) $validated['email']));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Nao foi possivel redefinir a senha. Confira os dados e tente novamente.'],
            ]);
        }

        $verifiedEmail = (string) $request->session()->get('password_reset_verified_email', '');
        $verifiedAt = $request->session()->get('password_reset_verified_at');

        $isSessionVerified = $verifiedEmail === $email && is_string($verifiedAt);
        if (! $isSessionVerified) {
            throw ValidationException::withMessages([
                'email' => ['Confirme o codigo enviado para seu email antes de alterar a senha.'],
            ]);
        }

        try {
            $verifiedAtTime = Carbon::parse($verifiedAt);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'email' => ['Codigo expirado. Solicite um novo codigo de redefinicao.'],
            ]);
        }

        if ($verifiedAtTime->addMinutes(15)->isPast()) {
            throw ValidationException::withMessages([
                'email' => ['Codigo expirado. Solicite um novo codigo de redefinicao.'],
            ]);
        }

        if (! $user->password_reset_code_verified_at || $user->password_reset_code_verified_at->addMinutes(15)->isPast()) {
            throw ValidationException::withMessages([
                'email' => ['Codigo nao confirmado. Solicite um novo codigo de redefinicao.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make((string) $validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));
        $user->clearPasswordResetCode();

        $request->session()->forget([
            'password_reset_verified_email',
            'password_reset_verified_at',
        ]);

        return redirect()
            ->route('legacy.login')
            ->with('status', 'Senha redefinida com sucesso. Faca login com a nova senha.');
    }
}
