<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LigaClubeElenco;
use App\Models\LigaPeriodo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create()
    {
        return response()->view('login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $redirectTo = $request->session()->pull('url.intended', route('dashboard', absolute: false));
        $lockedLigaId = $this->resolveRosterLockLigaId($request->user());

        if ($lockedLigaId) {
            $redirectTo = route('minha_liga.meu_elenco', ['liga_id' => $lockedLigaId], absolute: false);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Login realizado com sucesso.',
                'redirect' => $redirectTo,
            ]);
        }

        return redirect()->to($redirectTo);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function resolveRosterLockLigaId($user): ?int
    {
        if (! $user) {
            return null;
        }

        $clubes = $user->clubesLiga()->with('liga')->get(['id', 'liga_id', 'user_id']);

        foreach ($clubes as $clube) {
            $liga = $clube->liga;

            if (! $liga) {
                continue;
            }

            if (! LigaPeriodo::activeRangeForLiga($liga)) {
                continue;
            }

            $activeCount = LigaClubeElenco::query()
                ->where('liga_id', $liga->id)
                ->where('liga_clube_id', $clube->id)
                ->where('ativo', true)
                ->count();

            if ($activeCount > 18) {
                return (int) $liga->id;
            }
        }

        return null;
    }
}
