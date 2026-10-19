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

        // Ventas del mes (pedidos con pago confirmado)
        $totalVentasMes = Order::whereIn('estado', Order::ESTADOS_PAGADOS)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total');

        // Cantidad de pedidos en el mes
        $pedidosMes = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

        // Mismo tramo del mes anterior (del día 1 al mismo día), para comparar de forma justa
        $inicioMesAnterior = $startOfMonth->copy()->subMonthNoOverflow();
        $finTramoAnterior = $inicioMesAnterior->copy()->addDays($now->day - 1)->endOfDay()->min($inicioMesAnterior->copy()->endOfMonth());
        $ventasMesAnterior = Order::whereIn('estado', Order::ESTADOS_PAGADOS)
            ->whereBetween('created_at', [$inicioMesAnterior, $finTramoAnterior])
            ->sum('total');
        $pedidosMesAnterior = Order::whereBetween('created_at', [$inicioMesAnterior, $finTramoAnterior])->count();
        $variacion = fn ($actual, $anterior) => $anterior > 0 ? round(($actual - $anterior) / $anterior * 100, 1) : null;

        // Productos activos en o bajo su stock mínimo (los 5 más críticos)
        $productosStockBajo = Product::where('activo', true)
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->orderByRaw('stock - stock_minimo')
            ->limit(5)
            ->get(['id', 'nombre', 'codigo', 'stock', 'stock_minimo']);

        // Total de clientes (usuarios con rol 'cliente')
        $totalClientes = DB::table('users')
            ->join('roles', 'users.rol_id', '=', 'roles.id')
            ->where('roles.nombre', 'cliente')
            ->count();

        // Pedidos pendientes
        $pedidosPendientes = Order::where('estado', 'pendiente')->count();

        // Pagos por verificar
        $pagosPorVerificar = Payment::whereIn('estado', ['pendiente', 'sospechoso'])->count();

        // Cantidad de productos con stock <= stock_minimo
        $cantidadStockBajo = Product::whereColumn('stock', '<=', 'stock_minimo')->count();

        // Ventas por categoría (sumando subtotal de items de pedidos con pago confirmado)
        $ventasPorCategoria = DB::table('items_pedido')
            ->join('products', 'items_pedido.producto_id', '=', 'products.id')
            ->join('categorias', 'products.categoria_id', '=', 'categorias.id')
            ->join('pedidos', 'items_pedido.pedido_id', '=', 'pedidos.id')
            ->whereIn('pedidos.estado', Order::ESTADOS_PAGADOS)
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
            $sum = Order::whereIn('estado', Order::ESTADOS_PAGADOS)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->sum('total');
            $ventasSemanales[] = [
                'label' => $weekStart->format('d/m'),
                'total' => (float)$sum,
            ];
        }

        return [
            'total_ventas_mes' => (float)$totalVentasMes,
            'total_ventas_mes_bs' => \App\Services\ServicioPrecios::convertirFromUsd((float) $totalVentasMes, 'bs'),
            'ventas_mes_trend' => $variacion((float) $totalVentasMes, (float) $ventasMesAnterior),
            'pedidos_mes_trend' => $variacion($pedidosMes, $pedidosMesAnterior),
            'productos_stock_bajo' => $productosStockBajo,
            'pedidos_mes' => (int)$pedidosMes,
            'total_clientes' => (int)$totalClientes,
            'pedidos_pendientes' => (int)$pedidosPendientes,
            'pagos_por_verificar' => (int)$pagosPorVerificar,
            'cantidad_stock_bajo' => (int)$cantidadStockBajo,
            'alertas_stock' => (int)$cantidadStockBajo, // alias de cantidad_stock_bajo, usado por el sidebar
            'ventas_por_categoria' => $ventasPorCategoria,
            'ventas_semanales' => $ventasSemanales,
        ];
    }
}
