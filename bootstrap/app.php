<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Registrar aliases personalizados para middlewares de la aplicación
        $middleware->alias([
            'admin' => \App\Http\Middleware\MiddlewareAdmin::class,
            'active' => \App\Http\Middleware\MiddlewareUsuarioActivo::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            session()->flash('error', 'Debes iniciar sesión para continuar. Si no tienes cuenta, puedes crear una en segundos.');
            return route('web.login');
        });

        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
