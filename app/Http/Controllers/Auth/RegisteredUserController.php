<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create()
    {
        return response()->view('register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Rules\Password::defaults()],
        ], [
            'email.required' => 'Informe seu e-mail para criar a conta.',
            'email.email' => 'Digite um e-mail valido.',
            'email.max' => 'Seu e-mail deve ter no maximo 255 caracteres.',
            'email.lowercase' => 'Digite o e-mail apenas com letras minusculas.',
            'email.unique' => 'Ja existe uma conta com este e-mail. Tente entrar ou recuperar a senha.',
            'password.required' => 'Informe uma senha para continuar.',
            'password.min' => 'Sua senha precisa ter pelo menos :min caracteres.',
            'password.letters' => 'Sua senha precisa incluir pelo menos uma letra.',
            'password.mixed' => 'Sua senha precisa incluir letra maiuscula e minuscula.',
            'password.numbers' => 'Sua senha precisa incluir pelo menos um numero.',
            'password.symbols' => 'Sua senha precisa incluir pelo menos um simbolo.',
            'password.uncompromised' => 'Essa senha e muito comum. Escolha outra mais segura.',
        ]);

        $email = (string) $validated['email'];
        $name = Str::of(strstr($email, '@', true) ?: 'usuario')
            ->replace(['.', '_', '-'], ' ')
            ->title()
            ->limit(255, '')
            ->toString();

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make((string) $validated['password']),
        ]);

        Profile::create([
            'user_id' => $user->id,
            'whatsapp' => null,
            'regiao' => 'Brasil',
            'idioma' => 'Português do Brasil',
            'reputacao_score' => 99,
            'nivel' => 0,
        ]);

        event(new Registered($user));

        Auth::login($user);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Cadastro realizado com sucesso.',
                'redirect' => route('verification.notice', absolute: false),
            ], 201);
        }

        return redirect(route('verification.notice', absolute: false));
    }
}
