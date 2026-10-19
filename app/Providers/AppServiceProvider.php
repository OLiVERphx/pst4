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
