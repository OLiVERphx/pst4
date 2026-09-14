<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * MiddlewareUsuarioActivo: verifica que el usuario esté activo.
 */
class MiddlewareUsuarioActivo
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && !$user->activo) {
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
            Auth::logout();
            return redirect('/')->with('error', 'Cuenta inactiva.');
        }

        return $next($request);
    }
}
