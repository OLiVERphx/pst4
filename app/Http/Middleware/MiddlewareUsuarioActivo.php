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
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $login = $request->is(config('app.admin_path') . '*') ? 'admin.login' : 'web.login';
            return redirect()->route($login)->withErrors(['email' => 'Tu cuenta está desactivada. Comunícate con Smartphone World.']);
        }

        return $next($request);
    }
}
