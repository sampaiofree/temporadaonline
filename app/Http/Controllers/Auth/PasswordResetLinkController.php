<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('legacy.auth.forgot-password', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Informe um email.',
            'email.email' => 'Informe um email valido.',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', 'Se o email existir em nossa base, enviaremos um link de redefinicao.');
        }

        if ($status == Password::RESET_THROTTLED) {
            throw ValidationException::withMessages([
                'email' => ['Aguarde alguns minutos antes de solicitar um novo link.'],
            ]);
        }

        throw ValidationException::withMessages([
            'email' => ['Nao foi possivel enviar o link de redefinicao. Tente novamente.'],
        ]);
    }
}
