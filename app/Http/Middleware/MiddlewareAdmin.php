<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * MiddlewareAdmin: asegura que el usuario tenga rol admin, superadmin o vendedor para entrar al panel.
 */
class MiddlewareAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->hasAnyRole(['admin', 'superadmin', 'vendedor'])) {
            return redirect('/admin/login')->with('error', 'No autorizado');
        }

        return $next($request);
    }
}
