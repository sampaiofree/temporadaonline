<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Plataforma;
use App\Models\Jogo;
use App\Models\Geracao;
use App\Models\Profile;

class ProfileController extends Controller
{
    /**
     * Show the logged-in player profile page with hydrated data.
     */
    public function show(Request $request): View
    {
        return view('perfil', [
            'player' => $this->playerPayload($request->user()),
            'plataformas' => Plataforma::orderBy('nome')->get(['id', 'nome']),
            'jogos' => Jogo::orderBy('nome')->get(['id', 'nome']),
            'geracoes' => Geracao::orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $payload = $request->validated();
        $originalEmail = $user?->email;

        if (array_key_exists('plataforma_id', $payload) && $payload['plataforma_id']) {
            $payload['plataforma'] = Plataforma::find($payload['plataforma_id'])?->nome;
        }

        if (array_key_exists('jogo_id', $payload) && $payload['jogo_id']) {
            $payload['jogo'] = Jogo::find($payload['jogo_id'])?->nome;
        }

        if (array_key_exists('geracao_id', $payload) && $payload['geracao_id']) {
            $payload['geracao'] = Geracao::find($payload['geracao_id'])?->nome;
        }

        $user->fill([
            'name' => $payload['nome'] ?? $payload['name'] ?? $user->name,
            'email' => $payload['email'] ?? $user->email,
        ]);

        if (isset($payload['email']) && $payload['email'] !== $originalEmail) {
            $user->email_verified_at = null;
        }

        $user->save();

        $profile = $user->profile;
        if (! $profile) {
            $profile = new Profile(['user_id' => $user->id]);
        }

        $profile->fill([
            'nickname' => $payload['nickname'] ?? $profile->nickname,
            'plataforma_id' => $payload['plataforma_id'] ?? $profile->plataforma_id,
            'jogo_id' => $payload['jogo_id'] ?? $profile->jogo_id,
            'geracao_id' => $payload['geracao_id'] ?? $profile->geracao_id,
            'plataforma' => $payload['plataforma'] ?? $profile->plataforma,
            'jogo' => $payload['jogo'] ?? $profile->jogo,
            'geracao' => $payload['geracao'] ?? $profile->geracao,
        ]);

        $profile->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Perfil atualizado com sucesso.',
                'player' => $this->playerPayload($user),
            ]);
        }

        return Redirect::to('/profile')->with('status', 'Perfil atualizado com sucesso.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function playerPayload(?Authenticatable $player): ?array
    {
        if (! $player) {
            return null;
        }

        $user = $player;
        $profile = method_exists($user, 'profile') ? $user->profile : null;

        return [
            'id' => $user->getAuthIdentifier(),
            'nome' => $user->name ?? null,
            'nickname' => $profile?->nickname,
            'email' => $user->email ?? null,
            'plataforma' => $profile?->plataforma_nome,
            'plataforma_id' => $profile?->plataforma_id,
            'geracao' => $profile?->geracao_nome,
            'geracao_id' => $profile?->geracao_id,
            'jogo' => $profile?->jogo_nome,
            'jogo_id' => $profile?->jogo_id,
            'regiao' => $profile?->regiao,
            'idioma' => $profile?->idioma,
            'reputacao_score' => $profile?->reputacao_score,
            'nivel' => $profile?->nivel,
            'avatar' => $profile?->avatar,
        ];
    }
}
