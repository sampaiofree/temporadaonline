<?php

namespace App\Http\Controllers\Legacy\Auth;

use App\Http\Controllers\Concerns\ChecksProfileCompletion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Models\UserDisponibilidade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    use ChecksProfileCompletion;

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            if ($this->requiresFirstAccess($request->user())) {
                return redirect()->route('legacy.primeiro_acesso');
            }

            return redirect()->route('legacy.index');
        }

        return view('legacy.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('legacy.index');
        }

        $request->authenticate();
        $request->session()->regenerate();

        if ($this->requiresFirstAccess($request->user())) {
            return redirect()->route('legacy.primeiro_acesso');
        }

        return redirect()->intended(route('legacy.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('legacy.login');
    }

    private function requiresFirstAccess(?User $user): bool
    {
        if (! $user) {
            return true;
        }

        $hasAvailability = UserDisponibilidade::query()
            ->where('user_id', $user->id)
            ->exists();

        return ! ($this->hasCompleteProfile($user->profile) && $hasAvailability);
    }
}
