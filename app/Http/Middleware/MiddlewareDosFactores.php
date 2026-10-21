<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Verificación en dos pasos del panel.
 *
 * Se revisa en cada petición (no solo en el login): así no se salta entrando por el
 * login de la tienda, que usa la misma sesión.
 *  - Con 2FA activo y sin código en esta sesión: solo puede ver la pantalla del código.
 *  - Rol que la exige y sin 2FA configurado: solo puede ver la pantalla para activarlo.
 */
class MiddlewareDosFactores
{
    public const SESION = 'dos_factores.verificado';

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($user->tieneDosFactores()) {
            if ($request->session()->get(self::SESION) === $user->id || $request->routeIs('admin.2fa.desafio', 'admin.2fa.verificar')) {
                return $next($request);
            }

            return $this->desviar($request, 'admin.2fa.desafio');
        }

        if ($user->requiereDosFactores() && ! $request->routeIs('admin.2fa.configurar', 'admin.2fa.activar', 'admin.2fa.codigos')) {
            return $this->desviar($request, 'admin.2fa.configurar');
        }

        return $next($request);
    }

    private function desviar(Request $request, string $ruta)
    {
        // Peticiones de Livewire/JSON: no se redirige una acción a medias
        if ($request->expectsJson() || ! $request->isMethod('GET')) {
            abort(403, 'Verificación en dos pasos requerida.');
        }

        if ($ruta === 'admin.2fa.desafio') {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->route($ruta);
    }
}
