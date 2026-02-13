<?php

namespace App\Http\Controllers\Legacy\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
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

        return redirect()->intended(route('legacy.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('legacy.login');
    }
}
