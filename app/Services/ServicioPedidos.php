<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Servicio para operaciones sobre pedidos (avanzar estados, cancelar, etc.)
 */
class ServicioPedidos
{
    protected ServicioInventario $inventarioService;

    public function __construct(ServicioInventario $inventarioService)
    {
        $this->inventarioService = $inventarioService;
    }

    /**
     * Crea un pedido a partir de líneas ya validadas.
     *
     * Ejecuta la transacción, hace lockForUpdate sobre productos, ajusta stock,
     * crea items y registra auditoría y movimientos de inventario.
     *
     * @param array $lineas Cada línea: ['producto_id'=>int,'cantidad'=>int,'precio'=>float]
     * @param int|null $usuarioId ID del cliente (puede ser null para consumidor final)
     * @param int $vendedorId ID del usuario que registra la venta (vendedor/admin)
     * @param string $canal 'online'|'local'
     * @param string|null $notas Notas de entrega o internas
     * @return Order
     * @throws \Exception
     */
    public function crearPedidoDesdeLineas(array $lineas, ?int $usuarioId, int $vendedorId, string $canal = 'online', ?string $notas = null, array $datosEntrega = []): Order
    {
        // Recalcular totales y validar stock dentro de la transacción
        return DB::transaction(function () use ($lineas, $usuarioId, $vendedorId, $canal, $notas, $datosEntrega) {
            $subtotal = 0;
            $detalles = [];

            foreach ($lineas as $l) {
                $producto = Product::where('id', $l['producto_id'])->lockForUpdate()->first();
                if (!$producto) {
                    throw new \Exception('Producto no encontrado: ' . $l['producto_id']);
                }

                $disponible = $producto->stock - $producto->stock_reservado;
                if ($disponible < $l['cantidad']) {
                    throw new \Exception('Stock insuficiente para ' . $producto->nombre);
                }

                // Al mayor: cada producto exige su cantidad mínima (en la tienda y en venta local)
                if (($l['tipo'] ?? null) === 'mayor') {
                    $minimo = (int) ($producto->min_cantidad_mayor ?: (\App\Models\Configuracion::instance()?->minimo_unidades_mayor ?? 10));
                    if ($l['cantidad'] < $minimo) {
                        throw new \Exception("Para comprar {$producto->nombre} al mayor se necesitan al menos {$minimo} unidades (llevas {$l['cantidad']}).");
                    }
                }

                $precio = $l['precio'];
                $sub = $precio * $l['cantidad'];
                $subtotal += $sub;

                $detalles[] = ['producto' => $producto, 'cantidad' => $l['cantidad'], 'precio' => $precio, 'subtotal' => $sub, 'tipo' => $l['tipo'] ?? null];
            }

            // Validación de mínimo para ventas al mayor: construir lista de items al mayor y validar
            $itemsMayor = array_filter($detalles, function($x) {
                return isset($x['tipo']) && $x['tipo'] === 'mayor';
            });

            if (!empty($itemsMayor)) {
                $cartData = ['items' => [], 'is_wholesale' => true];
                foreach ($itemsMayor as $im) {
                    $cartData['items'][] = ['product_id' => $im['producto']->id, 'cantidad' => $im['cantidad'], 'unit_price' => $im['precio'], 'tipo' => $im['tipo'] ?? 'mayor'];
                }

                $resultado = \App\Services\ServicioPrecios::validarMinimoMayor($cartData);
                if ($resultado !== null) {
                    throw new \Exception($resultado['message']);
                }
            }

            // Crear pedido (con reintento si otro pedido simultáneo tomó el mismo número)
            $datosPedido = [
                'usuario_id'    => $usuarioId,
                'tipo'          => collect($detalles)->contains(fn($x) => isset($x['producto']) && ($x['producto']->precio_mayor < $x['producto']->precio_detal) && false) ? 'mayor' : 'detal',
                'estado'        => 'pendiente',
                'subtotal'      => $subtotal,
                'descuento'     => 0,
                'total'         => $subtotal,
                'tipo_entrega'  => $datosEntrega['tipo_entrega'] ?? 'retiro',
                'entrega_nombre' => $datosEntrega['entrega_nombre'] ?? null,
                'entrega_telefono' => $datosEntrega['entrega_telefono'] ?? null,
                'entrega_direccion' => $datosEntrega['entrega_direccion'] ?? null,
                'entrega_ciudad' => $datosEntrega['entrega_ciudad'] ?? null,
                'entrega_notas' => $notas,
                'canal' => $canal,
            ];
            for ($intento = 1; ; $intento++) {
                try {
                    $order = Order::create(['numero_pedido' => Order::generarNumeroPedido()] + $datosPedido);
                    break;
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    if ($intento >= 3) {
                        throw $e;
                    }
                }
            }

            // Crear items y ajustar stock
            foreach ($detalles as $d) {
                OrderItem::create([
                    'pedido_id' => $order->id,
                    'producto_id' => $d['producto']->id,
                    'cantidad' => $d['cantidad'],
                    'precio_unitario' => $d['precio'],
                    'subtotal' => $d['subtotal'],
                ]);

                // El checkout libera la reserva temporal del cliente antes de crear el pedido,
                // así que aquí no se toca stock_reservado: el movimiento de salida descuenta el stock.
                $producto = Product::where('id', $d['producto']->id)->lockForUpdate()->first();

                // Registrar movimiento de inventario
                $this->inventarioService->registrarMovimiento(
                    $producto, 'salida', $d['cantidad'], $order->numero_pedido, 'Venta (' . $canal . ')', 
                    
                    // pasar modelo de usuario para extraer nombre/email en el movimiento
                    User::find($vendedorId)
                );
            }

            // Crear registro de pago pendiente solo para ventas locales (pago en efectivo en caja).
            // Las ventas online crean su propio Payment en el CheckoutController con el método real.
            if ($canal === 'local') {
                Payment::create([
                    'pedido_id' => $order->id,
                    'metodo' => 'fisico',
                    'monto' => $subtotal,
                    'monto_declarado' => null,
                    'moneda_declarada' => null,
                    'fecha_pago_declarada' => null,
                    'moneda' => config('pagos.default_moneda','VEF'),
                    'numero_referencia' => null,
                    'estado' => 'pendiente',
                ]);
            }

            // Registrar en la bitácora de auditoría que se creó el pedido local
            ServicioAuditoria::registrar(
                'pedido.creado',
                $order,
                null,
                $order->toArray(),
                $vendedorId
            );

            return $order->fresh();
        });
    }

    /**
     * Determina el flujo de estados aplicable a un pedido según su tipo de entrega
     * y el método de pago real registrado en su Payment.
     */
    public function obtenerFlujoParaPedido(Order $order): array
    {
        $metodo = $order->payment?->metodo ?? 'fisico';

        if ($order->tipo_entrega === 'delivery') {
            return ['pendiente', 'pago_subido', 'pago_a_confirmar', 'pago_confirmado', 'en_camino', 'entregado'];
        }

        if ($metodo === 'fisico') {
            return ['pendiente', 'listo_para_retirar', 'completado'];
        }

        return ['pendiente', 'pago_subido', 'pago_a_confirmar', 'pago_confirmado_retirar', 'completado'];
    }

    /**
     * Avanza el estado del pedido según el flujo definido.
     *
     * @param Order $order
     * @param User $admin
     * @return Order
     */
    public function avanzarEstado(Order $order, User $admin): Order
    {
        $flow = $this->obtenerFlujoParaPedido($order);
        $current = $order->estado;
        $pos = array_search($current, $flow, true);

        if ($pos === false) {
            // Estado desconocido, no hacer nada
            return $order;
        }

        $nextPos = min($pos + 1, count($flow) - 1);

        return $this->aplicarEstado($order, $flow[$nextPos], $admin);
    }

    /**
     * Lleva el pedido directamente al estado de pago confirmado de su flujo
     * (pago_confirmado, pago_confirmado_retirar o, en pago en tienda, completado).
     * Se usa al aprobar un pago:
     * no tiene sentido dejarlo en un paso intermedio. Si el pedido ya está en
     * ese estado o más adelante, no lo retrocede.
     */
    public function confirmarPago(Order $order, User $admin): Order
    {
        $flow = $this->obtenerFlujoParaPedido($order);
        // Pago en tienda (venta local o retiro + efectivo): no hay estado intermedio,
        // el pago se cobra al entregar y la venta queda completada.
        $objetivo = collect($flow)->first(fn ($e) => str_starts_with($e, 'pago_confirmado'))
            ?? (in_array('completado', $flow, true) ? 'completado' : null);
        $pos = array_search($order->estado, $flow, true);

        if ($objetivo === null || $pos === false || $pos >= array_search($objetivo, $flow, true)) {
            return $order;
        }

        return $this->aplicarEstado($order, $objetivo, $admin);
    }

    private function aplicarEstado(Order $order, string $next, User $admin): Order
    {
        $current = $order->estado;

        $antes = [
            'estado' => $current,
            'confirmado_por' => $order->confirmado_por,
            'confirmado_en' => $order->confirmado_en ? (string) $order->confirmado_en : null,
        ];

        DB::transaction(function () use ($order, $next, $admin) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first() ?? $order;
            $lockedOrder->estado = $next;

            if (str_starts_with($next, 'pago_confirmado')) {
                $lockedOrder->confirmado_por = $admin->id;
                $lockedOrder->confirmado_en = Carbon::now();
            }

            $lockedOrder->save();

            $order->estado = $lockedOrder->estado;
            $order->confirmado_por = $lockedOrder->confirmado_por;
            $order->confirmado_en = $lockedOrder->confirmado_en;
        });

        $despues = [
            'estado' => $order->estado,
            'confirmado_por' => $order->confirmado_por,
            'confirmado_en' => $order->confirmado_en ? (string) $order->confirmado_en : null,
        ];

        ServicioAuditoria::registrar(
            'pedido.estado_cambiado',
            $order,
            $antes,
            $despues,
            $admin->id
        );

        return $order->fresh();
    }

    /**
     * Cancela un pedido, registra motivo y libera stock reservado.
     *
     * @param Order $order
     * @param User $admin
     * @param string $reason
     * @return Order
     */
    /** Estados en los que un pedido ya no se puede cancelar (la mercancía se entregó o ya se liberó). */
    public const ESTADOS_NO_CANCELABLES = ['cancelado', 'completado', 'entregado'];

    public function cancel(Order $order, User $admin, string $reason): Order
    {
        // Cancelar dos veces devolvía el stock dos veces; cancelar uno entregado lo devolvía sin tener la mercancía
        if (in_array($order->estado, self::ESTADOS_NO_CANCELABLES, true)) {
            throw new \RuntimeException('Un pedido ' . \App\Helpers\LabelHelper::translateStatus($order->estado) . ' no se puede cancelar.');
        }

        $antes = [
            'estado' => $order->estado,
            'cancelado_por' => $order->cancelado_por,
            'motivo_cancelacion' => $order->motivo_cancelacion,
        ];

        DB::transaction(function () use ($order, $admin, $reason) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first() ?? $order;
            $lockedOrder->estado = 'cancelado';
            $lockedOrder->cancelado_por = $admin->id;
            $lockedOrder->motivo_cancelacion = $reason;
            $lockedOrder->save();

            $order->estado = $lockedOrder->estado;
            $order->cancelado_por = $lockedOrder->cancelado_por;
            $order->motivo_cancelacion = $lockedOrder->motivo_cancelacion;

            // Liberar stock: por cada item, incrementar stock con tipo liberacion
            foreach ($lockedOrder->items as $item) {
                $product = $item->product;
                if ($product) {
                    $this->inventarioService->registrarMovimiento($product, 'liberacion', (int)$item->cantidad, 'cancel_order_'.$order->id, 'Liberación por cancelación: ' . $reason, $admin);
                }
            }
        });

        $despues = [
            'estado' => $order->estado,
            'cancelado_por' => $order->cancelado_por,
            'motivo_cancelacion' => $order->motivo_cancelacion,
        ];

        ServicioAuditoria::registrar(
            'pedido.cancelado',
            $order,
            $antes,
            $despues,
            $admin->id
        );

        return $order->fresh();
    }

    /**
     * Obtener flujo de estados con etiquetas.
     *
     * @return array
     */
    public function obtenerFlujoEstados(): array
    {
        return [
            'pendiente' => 'Pendiente',
            'listo_para_retirar' => 'Listo para retirar',
            'pago_subido' => 'Pago subido',
            'pago_a_confirmar' => 'Pago a confirmar',
            'pago_confirmado_retirar' => 'Pago confirmado — puede retirar',
            'pago_confirmado' => 'Pago confirmado',
            'en_camino' => 'En camino',
            'entregado' => 'Entregado',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado',
            // Estados heredados de pedidos anteriores a esta ronda de cambios:
            'pago_verificado' => 'Pago verificado',
            'procesando' => 'Procesando',
            'enviado' => 'Enviado',
        ];
    }
}
