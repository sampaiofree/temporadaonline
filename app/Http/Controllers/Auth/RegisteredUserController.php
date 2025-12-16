<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Jogador;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:jogadores,email'],
            'whatsapp' => ['required', 'digits_between:10,15'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $jogador = Jogador::create([
            'nome' => $validated['nome'],
            'nickname' => $validated['nickname'] ?? null,
            'email' => $validated['email'],
            'whatsapp' => $validated['whatsapp'],
            'password' => Hash::make($validated['password']),
            'regiao' => 'Brasil',
            'idioma' => 'Português do Brasil',
            'reputacao_score' => 99,
            'nivel' => 0,
        ]);

        event(new Registered($jogador));

        Auth::login($jogador);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Cadastro realizado com sucesso.',
                'redirect' => route('dashboard', absolute: false),
            ], 201);
        }

        return redirect(route('dashboard', absolute: false));
    }
}
