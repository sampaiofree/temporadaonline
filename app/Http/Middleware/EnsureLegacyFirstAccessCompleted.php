<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Concerns\ChecksProfileCompletion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLegacyFirstAccessCompleted
{
    use ChecksProfileCompletion;

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isAllowedRoute($request)) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $hasAvailability = $user->disponibilidades()->exists();
        $isProfileComplete = $this->hasCompleteProfile($user->profile);

        if ($isProfileComplete && $hasAvailability) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Finalize o primeiro acesso antes de continuar.',
                'redirect' => route('legacy.primeiro_acesso'),
            ], 423);
        }

        return redirect()->route('legacy.primeiro_acesso');
    }

    private function isAllowedRoute(Request $request): bool
    {
        return $request->routeIs('legacy.primeiro_acesso')
            || $request->routeIs('legacy.primeiro_acesso.profile.update')
            || $request->routeIs('legacy.primeiro_acesso.disponibilidades.sync')
            || $request->routeIs('legacy.logout');
    }
}
