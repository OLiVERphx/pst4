<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Servicio para manejar reservas temporales de stock.
 */
class ReservationService
{
    /**
     * Reserva stock para un carrito concreto durante $minutes minutos.
     * Ejecuta lockForUpdate sobre cada producto y aumenta stock_reservado.
     * Devuelve array con resultados por item ['ok' => true/false, 'mensaje' => '']
     */
    public function reserveForCart(Cart $cart, int $minutes = 15)
    {
        $results = [];
        $now = Carbon::now();

        DB::transaction(function () use ($cart, $minutes, &$results, $now) {
            foreach ($cart->items()->with('producto')->get() as $item) {
                $producto = Product::where('id', $item->producto_id)->lockForUpdate()->first();
                if (!$producto) {
                    $results[] = ['ok' => false, 'item' => $item->id, 'mensaje' => 'Producto no existe'];
                    continue;
                }

                $disponible = $producto->stock - $producto->stock_reservado;
                if ($disponible < $item->cantidad) {
                    $results[] = ['ok' => false, 'item' => $item->id, 'mensaje' => "Stock insuficiente para producto {$producto->id}"];
                    continue;
                }

                $antes = ['stock' => $producto->stock, 'stock_reservado' => $producto->stock_reservado];

                $producto->stock_reservado = $producto->stock_reservado + $item->cantidad;
                $producto->save();

                \App\Services\ServicioAuditoria::registrar(
                    'reservar_stock',
                    $producto,
                    $antes,
                    ['stock' => $producto->stock, 'stock_reservado' => $producto->stock_reservado],
                    $cart->usuario_id
                );

                $results[] = ['ok' => true, 'item' => $item->id];
            }

            // Si hubo al menos un ok, marcamos el carrito como reserved
            if (collect($results)->containsStrict('ok', true)) {
                $cart->estado = 'reserved';
                $cart->reserved_at = Carbon::now()->addMinutes($minutes);
                $cart->save();
            }
        });

        return $results;
    }

    /**
     * Libera las reservas asociadas a un carrito (si existe stock_reservado > 0)
     */
    public function releaseCartReservations(Cart $cart)
    {
        DB::transaction(function () use ($cart) {
            foreach ($cart->items()->with('producto')->get() as $item) {
                $producto = Product::where('id', $item->producto_id)->lockForUpdate()->first();
                if (!$producto) continue;

                $antes = ['stock' => $producto->stock, 'stock_reservado' => $producto->stock_reservado];
                $producto->stock_reservado = max(0, $producto->stock_reservado - $item->cantidad);
                $producto->save();

                \App\Services\ServicioAuditoria::registrar(
                    'liberar_reserva',
                    $producto,
                    $antes,
                    ['stock' => $producto->stock, 'stock_reservado' => $producto->stock_reservado],
                    $cart->usuario_id
                );
            }

            $cart->estado = 'active';
            $cart->reserved_at = null;
            $cart->save();
        });
    }
}
