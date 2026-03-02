<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only('email', 'password');
        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => $this->isLegacyLoginRequest()
                    ? 'Nao conseguimos entrar com estes dados. Confira e-mail e senha e tente novamente.'
                    : trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $legacyThrottleMessage = $seconds >= 60
            ? 'Muitas tentativas de login. Aguarde cerca de '.ceil($seconds / 60).' minuto(s) e tente novamente.'
            : 'Muitas tentativas de login. Aguarde '.$seconds.' segundo(s) e tente novamente.';

        throw ValidationException::withMessages([
            'email' => $this->isLegacyLoginRequest()
                ? $legacyThrottleMessage
                : trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
        ]);
    }

    public function messages(): array
    {
        if (! $this->isLegacyLoginRequest()) {
            return [];
        }

        return [
            'email.required' => 'Informe seu e-mail para continuar.',
            'email.email' => 'Digite um e-mail valido.',
            'password.required' => 'Informe sua senha para continuar.',
        ];
    }

    private function isLegacyLoginRequest(): bool
    {
        return $this->routeIs('legacy.*');
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
