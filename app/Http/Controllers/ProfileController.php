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
use App\Models\Idioma;
use App\Models\Profile;
use App\Models\Regiao;

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
            'regioes' => Regiao::orderBy('nome')->get(['id', 'nome']),
            'idiomas' => Idioma::orderBy('nome')->get(['id', 'nome']),
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
            'whatsapp' => $payload['whatsapp'] ?? $profile->whatsapp,
            'regiao_id' => $payload['regiao_id'] ?? $profile->regiao_id,
            'idioma_id' => $payload['idioma_id'] ?? $profile->idioma_id,
        ]);

        if (array_key_exists('regiao_id', $payload)) {
            $profile->regiao = $payload['regiao_id']
                ? Regiao::query()->find($payload['regiao_id'])?->nome
                : null;
        }

        if (array_key_exists('idioma_id', $payload)) {
            $profile->idioma = $payload['idioma_id']
                ? Idioma::query()->find($payload['idioma_id'])?->nome
                : null;
        }

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
            'whatsapp' => $profile?->whatsapp,
            'plataforma' => $profile?->plataforma_nome,
            'plataforma_id' => $profile?->plataforma_id,
            'geracao' => $profile?->geracao_nome,
            'geracao_id' => $profile?->geracao_id,
            'jogo' => $profile?->jogo_nome,
            'jogo_id' => $profile?->jogo_id,
            'regiao' => $profile?->regiao_nome,
            'regiao_id' => $profile?->regiao_id,
            'idioma' => $profile?->idioma_nome,
            'idioma_id' => $profile?->idioma_id,
            'reputacao_score' => $profile?->reputacao_score,
            'nivel' => $profile?->nivel,
            'avatar' => $profile?->avatar,
        ];
    }
}
