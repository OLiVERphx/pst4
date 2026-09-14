<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

/**
 * Servicio para gestionar el flujo de pedidos (avanzar estado, cancelar, etc.)
 */
class ServicioPedidos
{
    /**
     * Avanza el estado del pedido siguiendo el flujo configurado.
     * Registra confirmado_por y confirmado_en cuando pasa a 'procesando'.
     *
     * @param Order $order
     * @param User $admin
     * @return Order
     */
    public function avanzarEstado(Order $order, User $admin): Order
    {
        $flow = ['pendiente', 'pago_subido', 'pago_verificado', 'procesando', 'enviado', 'entregado'];
        $idx = array_search($order->estado, $flow);
        if ($idx === false || $idx >= count($flow) - 1) {
            return $order;
        }

        $next = $flow[$idx + 1];
        $order->estado = $next;

        if ($next === 'procesando') {
            $order->confirmado_por = $admin->id;
            $order->confirmado_en = Carbon::now();
        }

        $order->save();

        return $order;
    }

    /**
     * Cancela un pedido y libera stock reservado.
     *
     * @param Order $order
     * @param User $admin
     * @param string $reason
     * @return Order
     */
    public function cancel(Order $order, User $admin, string $reason): Order
    {
        $order->estado = 'cancelado';
        $order->cancelado_por = $admin->id;
        $order->motivo_cancelacion = $reason;
        $order->save();

        // Liberar stock (si aplicÃ³ reserva al crear el pedido)
        $inventario = new ServicioInventario();
        foreach ($order->items as $item) {
            $product = $item->product;
            if ($product) {
                $inventario->registrarMovimiento(
                    $product,
                    'liberacion',
                    (int) $item->cantidad,
                    'CancelaciÃ³n pedido ' . $order->numero_pedido,
                    null,
                    $admin
                );
            }
        }

        return $order;
    }

    /**
     * Retorna el flujo de estados usable por la UI.
     *
     * @return array
     */
    public function obtenerFlujoEstados(): array
    {
        return [
            ['key' => 'pendiente', 'label' => 'pendiente'],
            ['key' => 'pago_subido', 'label' => 'Pago subido'],
            ['key' => 'pago_verificado', 'label' => 'Pago verificado'],
            ['key' => 'procesando', 'label' => 'Procesando'],
            ['key' => 'enviado', 'label' => 'Enviado'],
            ['key' => 'entregado', 'label' => 'Entregado'],
            ['key' => 'cancelado', 'label' => 'Cancelado'],
        ];
    }
}

