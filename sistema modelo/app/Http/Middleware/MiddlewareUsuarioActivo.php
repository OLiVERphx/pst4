<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MiddlewareUsuarioActivo
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && ! $user->activo) {
            Auth::logout();

            return redirect('/')->with('error', 'Su cuenta está inactiva.');
        }

        return $next($request);
    }
}
