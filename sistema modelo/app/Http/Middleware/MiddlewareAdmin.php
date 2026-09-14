<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MiddlewareAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user || ! $user->hasAnyRole(['admin', 'superadmin'])) {
            return redirect('/')->with('error', 'Acceso restringido.');
        }

        return $next($request);
    }
}
