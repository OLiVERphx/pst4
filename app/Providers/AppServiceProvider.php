<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Correo de "¿Olvidaste tu contraseña?" en español
        \Illuminate\Auth\Notifications\ResetPassword::toMailUsing(function ($usuario, string $token) {
            $url = route('password.reset', ['token' => $token, 'email' => $usuario->getEmailForPasswordReset()]);

            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Restablecer tu contraseña - ' . config('app.name'))
                ->greeting('Hola, ' . $usuario->name)
                ->line('Recibimos una solicitud para cambiar la contraseña de tu cuenta.')
                ->action('Crear contraseña nueva', $url)
                ->line('El enlace vence en 60 minutos y sirve una sola vez.')
                ->line('Si no lo pediste, ignora este correo: tu contraseña no cambia.')
                ->salutation('Smartphone World C.A.');
        });

        // Verificación en dos pasos: cada inicio o cierre de sesión (panel, tienda o
        // "recordarme") la vuelve a pedir; las acciones de Livewire también la exigen.
        \Illuminate\Support\Facades\Event::listen(
            [\Illuminate\Auth\Events\Login::class, \Illuminate\Auth\Events\Logout::class],
            fn () => app()->bound('session.store') ? session()->forget(\App\Http\Middleware\MiddlewareDosFactores::SESION) : null
        );
        \Livewire\Livewire::addPersistentMiddleware([\App\Http\Middleware\MiddlewareDosFactores::class]);

        \Illuminate\Support\Facades\View::composer('layouts.admin', function ($view) {
            if (auth()->check()) {
                $stats = app(\App\Services\ServicioDashboard::class)->obtenerEstadisticas();
                $view->with('sidebarStats', $stats);
                $view->with('alertasCampana', self::alertasCampana(auth()->user(), $stats));
            }
        });
    }

    /** Avisos de la campana del panel según las preferencias y permisos del usuario. */
    public static function alertasCampana(\App\Models\User $user, array $stats): array
    {
        $alertas = [
            'pedido_nuevo' => [$stats['pedidos_pendientes'] ?? 0, 'pedido pendiente', 'pedidos pendientes', 'admin.pedidos.lista', '📦'],
            'pago_recibido' => [$stats['pagos_por_verificar'] ?? 0, 'pago por verificar', 'pagos por verificar', 'admin.payments.lista', '💳'],
            'stock_bajo' => [$stats['alertas_stock'] ?? 0, 'producto con stock bajo', 'productos con stock bajo', 'admin.inventario.alertas', '⚠️'],
        ];

        $resultado = [];
        foreach ($alertas as $tipo => [$cantidad, $singular, $plural, $ruta, $icono]) {
            if ($cantidad > 0 && $user->quiereNotificacion($tipo)) {
                $resultado[] = [
                    'texto' => $cantidad . ' ' . ($cantidad === 1 ? $singular : $plural),
                    'cantidad' => $cantidad,
                    'url' => route($ruta),
                    'icono' => $icono,
                ];
            }
        }

        return $resultado;
    }
}
