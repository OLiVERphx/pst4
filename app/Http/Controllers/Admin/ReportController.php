<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{

    /**
     * Mostrar reporte principal extendido con comparativos y proyecciones.
     */
    public function index()
    {
        // Top productos por cantidad vendida (mantener lógica existente)
        $topProducts = DB::table('items_pedido')
            ->join('products', 'items_pedido.producto_id', '=', 'products.id')
            ->select('products.id', 'products.nombre as producto', DB::raw('SUM(items_pedido.cantidad) as total'))
            ->groupBy('products.id', 'products.nombre')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->toArray();

        // Riesgo de stock (bajo respecto a stock_minimo)
        $lowStock = Product::whereColumn('stock', '<=', 'stock_minimo')->get()->toArray();

        // Estados considerados como pedidos "pagados/entregados" (reutilizar)
        $paidStatuses = ['entregado','pago_verificado','procesando','enviado'];

        // Ventas por categoría (mismo criterio que dashboard)
        $ventasPorCategoria = DB::table('items_pedido')
            ->join('products', 'items_pedido.producto_id', '=', 'products.id')
            ->join('categorias', 'products.categoria_id', '=', 'categorias.id')
            ->join('pedidos', 'items_pedido.pedido_id', '=', 'pedidos.id')
            ->whereIn('pedidos.estado', $paidStatuses)
            ->select('categorias.id as categoria_id','categorias.nombre as categoria', DB::raw('SUM(items_pedido.subtotal) as total'))
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        $totalOrders = Order::count();
        $totalFacturado = Order::whereIn('estado', $paidStatuses)->sum('total');
        $ordersPaidCount = Order::whereIn('estado', $paidStatuses)->count();
        $ticketPromedio = $ordersPaidCount ? ($totalFacturado / $ordersPaidCount) : 0;

        $totalClients = DB::table('users')
            ->join('roles', 'users.rol_id', '=', 'roles.id')
            ->where('roles.nombre', 'cliente')
            ->count();

        // --- Comparativos por periodo: mes actual vs mes anterior ---
        $now = \Carbon\Carbon::now();
        $startThis = $now->copy()->startOfMonth();
        $endThis = $now->copy()->endOfMonth();
        $startPrev = $now->copy()->subMonth()->startOfMonth();
        $endPrev = $now->copy()->subMonth()->endOfMonth();

        $facturadoThis = Order::whereIn('estado', $paidStatuses)
            ->whereBetween('created_at', [$startThis, $endThis])
            ->sum('total');

        $facturadoPrev = Order::whereIn('estado', $paidStatuses)
            ->whereBetween('created_at', [$startPrev, $endPrev])
            ->sum('total');

        // Variación porcentual: si prev == 0 devolvemos null para indicar N/A (evitar divisiones por cero)
        $facturadoVariationPct = null;
        if ($facturadoPrev > 0) {
            $facturadoVariationPct = (($facturadoThis - $facturadoPrev) / $facturadoPrev) * 100;
        } elseif ($facturadoPrev == 0 && $facturadoThis > 0) {
            $facturadoVariationPct = 100.0; // interpretación: crecimiento desde 0 a algo => 100%
        }

        // Comparativo por categoría (this vs prev)
        $ventasCategoriaThis = DB::table('items_pedido')
            ->join('products', 'items_pedido.producto_id', '=', 'products.id')
            ->join('categorias', 'products.categoria_id', '=', 'categorias.id')
            ->join('pedidos', 'items_pedido.pedido_id', '=', 'pedidos.id')
            ->whereIn('pedidos.estado', $paidStatuses)
            ->whereBetween('pedidos.created_at', [$startThis, $endThis])
            ->select('categorias.id','categorias.nombre', DB::raw('SUM(items_pedido.subtotal) as total'))
            ->groupBy('categorias.id','categorias.nombre')
            ->get()
            ->keyBy('id')
            ->map(function($r){ return (float)$r->total; })
            ->toArray();

        $ventasCategoriaPrev = DB::table('items_pedido')
            ->join('products', 'items_pedido.producto_id', '=', 'products.id')
            ->join('categorias', 'products.categoria_id', '=', 'categorias.id')
            ->join('pedidos', 'items_pedido.pedido_id', '=', 'pedidos.id')
            ->whereIn('pedidos.estado', $paidStatuses)
            ->whereBetween('pedidos.created_at', [$startPrev, $endPrev])
            ->select('categorias.id','categorias.nombre', DB::raw('SUM(items_pedido.subtotal) as total'))
            ->groupBy('categorias.id','categorias.nombre')
            ->get()
            ->keyBy('id')
            ->map(function($r){ return (float)$r->total; })
            ->toArray();

        // Construir arreglo final con variaciones por categoría
        $categoriaComparatives = [];
        foreach ($ventasPorCategoria as $cat) {
            $id = $cat->categoria_id ?? null;
            $thisTotal = $ventasCategoriaThis[$id] ?? 0.0;
            $prevTotal = $ventasCategoriaPrev[$id] ?? 0.0;
            $variation = null;
            if ($prevTotal > 0) {
                $variation = (($thisTotal - $prevTotal) / $prevTotal) * 100;
            } elseif ($prevTotal == 0 && $thisTotal > 0) {
                $variation = 100.0;
            }
            $categoriaComparatives[] = [
                'categoria' => $cat->categoria,
                'total' => (float)$cat->total,
                'this_period_total' => $thisTotal,
                'prev_period_total' => $prevTotal,
                'variation_pct' => $variation,
            ];
        }

        // --- Alerta: quiebre de stock proyectado (promedio ventas diarias últimos 30 días) ---
        // Nota: esta implementación usa un promedio simple (SUM(cantidad)/30). En el futuro
        // reemplazar por el modelo de regresión lineal (Python) descrito en marco teórico 2.2.6.
        // Punto de reemplazo futuro: aquí se puede invocar un servicio que consulte el modelo externo.

        $thirtyDaysAgo = $now->copy()->subDays(30);

        $productSalesLast30 = DB::table('items_pedido')
            ->join('pedidos', 'items_pedido.pedido_id', '=', 'pedidos.id')
            ->whereIn('pedidos.estado', $paidStatuses)
            ->whereBetween('pedidos.created_at', [$thirtyDaysAgo, $now])
            ->select('items_pedido.producto_id', DB::raw('SUM(items_pedido.cantidad) as sold_30'))
            ->groupBy('items_pedido.producto_id');

        // unir con products para obtener stock y datos básicos
        $stockProjection = DB::table(DB::raw("({$productSalesLast30->toSql()}) as recent"))
            ->mergeBindings($productSalesLast30)
            ->join('products', 'recent.producto_id', '=', 'products.id')
            ->select('products.id','products.nombre','products.stock', DB::raw('recent.sold_30'))
            ->get()
            ->map(function($row) {
                $avg_daily = ((float)$row->sold_30) / 30.0;
                if ($avg_daily <= 0.0) {
                    $days_to_deplete = null; // no ventas en periodo => no proyección
                } else {
                    $days_to_deplete = (float)$row->stock / $avg_daily;
                }
                return [
                    'product_id' => $row->id,
                    'product' => $row->nombre,
                    'stock' => (int)$row->stock,
                    'avg_daily_sales' => $avg_daily,
                    'days_to_deplete' => $days_to_deplete,
                ];
            })
            ->sortBy(function($r){ return $r['days_to_deplete'] ?? PHP_FLOAT_MAX; })
            ->values()
            ->toArray();

        // --- Reporte de anomalías de pagos: pagos rechazados por motivo ---
        // Se consideran anomalías los pagos con motivo_rechazo no nulo o estados que implican rechazo/sospecha
        $rejectionStates = ['invalido','invalid','sospechoso','suspicious'];

        $paymentAnomaliesByReason = DB::table('pagos')
            ->leftJoin('pedidos', 'pagos.pedido_id', '=', 'pedidos.id')
            ->leftJoin('users', 'pedidos.usuario_id', '=', 'users.id')
            ->where(function($q) use ($rejectionStates) {
                $q->whereNotNull('pagos.motivo_rechazo')
                  ->orWhereIn('pagos.estado', $rejectionStates);
            })
            ->select('pagos.motivo_rechazo', DB::raw('COUNT(*) as total'), DB::raw('GROUP_CONCAT(DISTINCT users.id) as user_ids'))
            ->groupBy('pagos.motivo_rechazo')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        // Para evitar exponer información sensible sin permiso, la vista debe pedir 'pagos.ver' antes de mostrar detalles.

        // Consumir microservicio ML externo si está disponible. Degradar con gracia si falla.
        $mlReport = null;
        try {
            $mlBase = env('ML_SERVICE_URL', 'http://ml-service:8001');
            $url = rtrim($mlBase, '/') . '/api/ml/reports';
            // timeout corto para no bloquear el panel; puede ajustarse según SLA
            $response = Http::timeout(5)->get($url);
            if ($response->successful()) {
                $mlReport = $response->json();
            } else {
                Log::warning('ML service returned non-200', ['status' => $response->status(), 'url' => $url]);
            }
        } catch (\Exception $e) {
            // No interrumpir el dashboard por fallo del servicio ML; registrar para monitorización
            Log::warning('ML service not available: ' . $e->getMessage());
            $mlReport = null;
        }

        return view('admin.reports.index', compact(
            'topProducts', 'lowStock', 'ventasPorCategoria',
            'totalOrders', 'totalFacturado', 'ticketPromedio', 'totalClients',
            'facturadoThis','facturadoPrev','facturadoVariationPct',
            'categoriaComparatives','stockProjection','paymentAnomaliesByReason', 'mlReport'
        ));
    }

    /**
     * Exportar reportes en CSV o Excel (Excel solo si librería está disponible).
     * Parám: report (string), format (csv|excel). Si el reporte requiere permisos adicionales
     * se revisa explícitamente (ej. 'payment_anomalies' requiere 'pagos.ver').
     */
    public function export(\Illuminate\Http\Request $request)
    {
        $this->authorize('reportes.ver');

        $report = $request->query('report', 'all');
        $format = $request->query('format', 'csv');

        // permisos adicionales
        if ($report === 'payment_anomalies') {
            $this->authorize('pagos.ver');
        }

        // Cargar los datos usando los métodos/consultas arriba (reusar index logic would duplicate work).
        // Para simplicidad y evitar duplicar demasiada lógica aquí, delegar a index-equivalentes minimal.

        switch ($report) {
            case 'stock_projection':
                // reproducir la consulta de proyección (30 días)
                $now = \Carbon\Carbon::now();
                $thirtyDaysAgo = $now->copy()->subDays(30);
                $paidStatuses = ['entregado','pago_verificado','procesando','enviado'];

                $productSalesLast30 = DB::table('items_pedido')
                    ->join('pedidos', 'items_pedido.pedido_id', '=', 'pedidos.id')
                    ->whereIn('pedidos.estado', $paidStatuses)
                    ->whereBetween('pedidos.created_at', [$thirtyDaysAgo, $now])
                    ->select('items_pedido.producto_id', DB::raw('SUM(items_pedido.cantidad) as sold_30'))
                    ->groupBy('items_pedido.producto_id');

                $rows = DB::table(DB::raw("({$productSalesLast30->toSql()}) as recent"))
                    ->mergeBindings($productSalesLast30)
                    ->join('products', 'recent.producto_id', '=', 'products.id')
                    ->select('products.id','products.nombre','products.stock', DB::raw('recent.sold_30'))
                    ->get()
                    ->map(function($row) {
                        $avg_daily = ((float)$row->sold_30) / 30.0;
                        $days_to_deplete = $avg_daily > 0 ? ((float)$row->stock / $avg_daily) : null;
                        return [
                            'product_id' => $row->id,
                            'product' => $row->nombre,
                            'stock' => (int)$row->stock,
                            'avg_daily_sales' => $avg_daily,
                            'days_to_deplete' => $days_to_deplete,
                        ];
                    })
                    ->sortBy(function($r){ return $r['days_to_deplete'] ?? PHP_FLOAT_MAX; })
                    ->values()
                    ->toArray();

                $filename = 'stock_projection_' . date('Ymd') . '.' . ($format === 'excel' ? 'xlsx' : 'csv');
                $headers = ['Producto','Stock','Promedio diario (30d)','Días hasta quiebre'];
                $dataRows = array_map(function($r){
                    return [$r['product'],$r['stock'],$r['avg_daily_sales'],$r['days_to_deplete']];
                }, $rows);
                break;

            case 'payment_anomalies':
                // reproducción de consulta
                $rejectionStates = ['invalido','invalid','sospechoso','suspicious'];
                $rows = DB::table('pagos')
                    ->leftJoin('pedidos', 'pagos.pedido_id', '=', 'pedidos.id')
                    ->leftJoin('users', 'pedidos.usuario_id', '=', 'users.id')
                    ->where(function($q) use ($rejectionStates) {
                        $q->whereNotNull('pagos.motivo_rechazo')
                          ->orWhereIn('pagos.estado', $rejectionStates);
                    })
                    ->select('pagos.motivo_rechazo', DB::raw('COUNT(*) as total'), DB::raw('GROUP_CONCAT(DISTINCT users.id) as user_ids'))
                    ->groupBy('pagos.motivo_rechazo')
                    ->orderByDesc('total')
                    ->get()
                    ->toArray();

                $filename = 'payment_anomalies_' . date('Ymd') . '.' . ($format === 'excel' ? 'xlsx' : 'csv');
                $headers = ['Motivo de rechazo','Total','Clientes afectados (IDs)'];
                $dataRows = array_map(function($r){ return [$r->motivo_rechazo ?? 'NO_ESPECIFICADO', $r->total, $r->user_ids]; }, $rows);
                break;

            default:
                // exportar un conjunto básico: topProducts + ventasPorCategoria
                $topProductsRows = DB::table('items_pedido')
                    ->join('products', 'items_pedido.producto_id', '=', 'products.id')
                    ->select('products.nombre as producto', DB::raw('SUM(items_pedido.cantidad) as total'))
                    ->groupBy('products.id','products.nombre')
                    ->orderByDesc('total')
                    ->limit(100)
                    ->get()
                    ->toArray();

                $ventasCatRows = DB::table('items_pedido')
                    ->join('products', 'items_pedido.producto_id', '=', 'products.id')
                    ->join('categorias', 'products.categoria_id', '=', 'categorias.id')
                    ->join('pedidos', 'items_pedido.pedido_id', '=', 'pedidos.id')
                    ->whereIn('pedidos.estado', ['entregado','pago_verificado','procesando','enviado'])
                    ->select('categorias.nombre as categoria', DB::raw('SUM(items_pedido.subtotal) as total'))
                    ->groupBy('categorias.id','categorias.nombre')
                    ->orderByDesc('total')
                    ->get()
                    ->toArray();

                // construir CSV con dos secciones
                $filename = 'reports_summary_' . date('Ymd') . '.csv';
                $headers = [];
                $dataRows = [];
                // section header placeholder handled in CSV generation
                break;
        }

        // Si se pide Excel y la librería está disponible, usarla. Si no, fallback a CSV.
        if ($format === 'excel' && class_exists(\Maatwebsite\Excel\Excel::class)) {
            // Se recomienda instalar maatwebsite/excel; aquí se delegaría la generación.
            // Para no añadir dependencia automática en este cambio, devolvemos 501 si no está instalada.
            // (Si está instalada, implementar export con un array y FromArray.)
            // NOTA: implementación detallada de Excel queda para una tarea posterior si el paquete se desea.
            return response('Export Excel no disponible: instalar maatwebsite/excel para habilitar.', 501);
        }

        // Generar CSV y forzar descarga
        $callback = function() use ($headers, $dataRows, $report) {
            $out = fopen('php://output', 'w');
            if (!empty($headers)) {
                fputcsv($out, $headers);
            }

            // Si export default 'all' build two sections
            if ($report === 'all') {
                // Top productos
                fputcsv($out, ['Top productos (nombre, total vendido)']);
                $top = DB::table('items_pedido')
                    ->join('products', 'items_pedido.producto_id', '=', 'products.id')
                    ->select('products.nombre as producto', DB::raw('SUM(items_pedido.cantidad) as total'))
                    ->groupBy('products.id','products.nombre')
                    ->orderByDesc('total')
                    ->limit(100)
                    ->get();
                fputcsv($out, ['Producto','Total']);
                foreach ($top as $r) {
                    fputcsv($out, [(string)$r->producto,(string)$r->total]);
                }

                fputcsv($out, []);
                fputcsv($out, ['Ventas por categoría (categoria, total)']);
                $cats = DB::table('items_pedido')
                    ->join('products', 'items_pedido.producto_id', '=', 'products.id')
                    ->join('categorias', 'products.categoria_id', '=', 'categorias.id')
                    ->join('pedidos', 'items_pedido.pedido_id', '=', 'pedidos.id')
                    ->whereIn('pedidos.estado', ['entregado','pago_verificado','procesando','enviado'])
                    ->select('categorias.nombre as categoria', DB::raw('SUM(items_pedido.subtotal) as total'))
                    ->groupBy('categorias.id','categorias.nombre')
                    ->orderByDesc('total')
                    ->get();
                fputcsv($out, ['Categoria','Total']);
                foreach ($cats as $c) {
                    fputcsv($out, [(string)$c->categoria,(string)$c->total]);
                }

                fclose($out);
                return;
            }

            foreach ($dataRows as $row) {
                // Normalizar arrays y valores
                fputcsv($out, array_map(function($v){ return is_null($v) ? '' : (string)$v; }, $row));
            }

            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    /**
     * Devuelve un arreglo asociativo [product_id => total_vendido] para los top productos.
     * Este método extrae la misma lógica que se usa en index() para que otros módulos
     * la reutilicen sin duplicar consulta.
     */
    public static function topSoldProducts(int $limit = 50): array
    {
        $rows = DB::table('items_pedido')
            ->join('products', 'items_pedido.producto_id', '=', 'products.id')
            ->select('products.id', DB::raw('SUM(items_pedido.cantidad) as total'))
            ->groupBy('products.id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->pluck('total', 'id')
            ->toArray();

        return $rows;
    }
}
