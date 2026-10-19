<?php

namespace App\Console\Commands;

use App\Mail\ReporteSemanal;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Envía por correo el resumen de la semana anterior (lunes a domingo) a los usuarios
 * del panel con permiso reportes.ver que tengan activo el aviso "Reporte semanal".
 * Programado los lunes en routes/console.php.
 */
class EnviarReporteSemanal extends Command
{
    protected $signature = 'reportes:semanal';

    protected $description = 'Envía el reporte semanal de ventas por correo';

    public function handle(): int
    {
        $destinatarios = User::permission('reportes.ver')
            ->where('activo', true)
            ->get()
            ->filter(fn (User $u) => $u->quiereNotificacion('reporte_semanal'));

        if ($destinatarios->isEmpty()) {
            $this->info('Nadie tiene activo el reporte semanal.');
            return self::SUCCESS;
        }

        $resumen = $this->resumen();
        foreach ($destinatarios as $usuario) {
            Mail::to($usuario->email)->send(new ReporteSemanal($resumen));
        }

        $this->info("Reporte semanal enviado a {$destinatarios->count()} usuario(s).");

        return self::SUCCESS;
    }

    public function resumen(): array
    {
        $desde = now()->startOfWeek()->subWeek();
        $hasta = $desde->copy()->endOfWeek();
        $anteriorDesde = $desde->copy()->subWeek();

        $pagados = fn ($inicio, $fin) => Order::whereIn('estado', Order::ESTADOS_PAGADOS)->whereBetween('created_at', [$inicio, $fin]);

        $ventas = (float) $pagados($desde, $hasta)->sum('total');
        $pedidos = $pagados($desde, $hasta)->count();
        $ventasAnterior = (float) $pagados($anteriorDesde, $desde->copy()->subSecond())->sum('total');

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'ventas' => $ventas,
            'pedidos' => $pedidos,
            'ticket' => $pedidos ? $ventas / $pedidos : 0,
            'variacion' => $ventasAnterior > 0 ? round(($ventas - $ventasAnterior) / $ventasAnterior * 100, 1) : null,
            'top' => DB::table('items_pedido')
                ->join('pedidos', 'items_pedido.pedido_id', '=', 'pedidos.id')
                ->join('products', 'items_pedido.producto_id', '=', 'products.id')
                ->whereIn('pedidos.estado', Order::ESTADOS_PAGADOS)
                ->whereBetween('pedidos.created_at', [$desde, $hasta])
                ->groupBy('products.id', 'products.nombre')
                ->orderByDesc('unidades')
                ->limit(5)
                ->get(['products.nombre', DB::raw('SUM(items_pedido.cantidad) as unidades')]),
            'stockBajo' => Product::where('activo', true)
                ->whereColumn('stock', '<=', 'stock_minimo')
                ->orderByRaw('stock - stock_minimo')
                ->get(['nombre', 'stock', 'stock_minimo']),
            'pedidosPendientes' => Order::where('estado', 'pendiente')->count(),
            'pagosPorVerificar' => Payment::whereIn('estado', ['pendiente', 'sospechoso'])->count(),
        ];
    }
}
