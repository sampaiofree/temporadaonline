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
            'nome' => ['required_without:name', 'string', 'max:255'],
            'name' => ['required_without:nome', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'whatsapp' => ['nullable', 'digits_between:10,15'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $name = $validated['nome'] ?? $validated['name'];

        $user = User::create([
            'name' => $name,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Profile::create([
            'user_id' => $user->id,
            'whatsapp' => $validated['whatsapp'] ?? null,
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
                'redirect' => route('dashboard', absolute: false),
            ], 201);
        }

        return redirect(route('dashboard', absolute: false));
    }
}
