<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Servicio para obtener estadísticas del dashboard.
 * Comentarios en español.
 */
class ServicioDashboard
{
    /**
     * Retorna un array con las estadísticas solicitadas.
     *
     * @return array
     */
    public function obtenerEstadisticas(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Ventas del mes (pedidos entregados)
        $totalVentasMes = Order::where('estado', 'entregado')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total');

        // Cantidad de pedidos en el mes
        $pedidosMes = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

        // Total de clientes (usuarios con rol 'cliente')
        $totalClientes = DB::table('users')
            ->join('roles', 'users.rol_id', '=', 'roles.id')
            ->where('roles.nombre', 'cliente')
            ->count();

        // Pedidos pendientes
        $pedidosPendientes = Order::where('estado', 'pendiente')->count();

        // Pagos por verificar
        $pagosPorVerificar = Payment::where('estado', 'pendiente')->count();

        // Cantidad de productos con stock <= stock_minimo
        $cantidadStockBajo = Product::whereColumn('stock', '<=', 'stock_minimo')->count();

        // Ventas por categoría (sumando subtotal de items de pedidos entregados)
        $ventasPorCategoria = DB::table('items_pedido')
            ->join('products', 'items_pedido.producto_id', '=', 'products.id')
            ->join('categorias', 'products.categoria_id', '=', 'categorias.id')
            ->join('pedidos', 'items_pedido.pedido_id', '=', 'pedidos.id')
            ->whereIn('pedidos.estado', ['entregado','pago_verificado','procesando','enviado'])
            ->select('categorias.nombre as categoria', DB::raw('SUM(items_pedido.subtotal) as total'))
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                return ['categoria' => $row->categoria, 'total' => (float)$row->total];
            })
            ->toArray();

        // Ventas semanales (últimas 8 semanas)
        $ventasSemanales = [];
        $startWeek = $now->copy()->startOfWeek()->subWeeks(7);
        for ($i = 0; $i < 8; $i++) {
            $weekStart = $startWeek->copy()->addWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            $sum = Order::where('estado', 'entregado')
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->sum('total');
            $ventasSemanales[] = [
                'label' => $weekStart->format('Y-m-d'),
                'total' => (float)$sum,
            ];
        }

        return [
            'total_ventas_mes' => (float)$totalVentasMes,
            'pedidos_mes' => (int)$pedidosMes,
            'total_clientes' => (int)$totalClientes,
            'pedidos_pendientes' => (int)$pedidosPendientes,
            'pagos_por_verificar' => (int)$pagosPorVerificar,
            'cantidad_stock_bajo' => (int)$cantidadStockBajo,
            'ventas_por_categoria' => $ventasPorCategoria,
            'ventas_semanales' => $ventasSemanales,
        ];
    }
}
