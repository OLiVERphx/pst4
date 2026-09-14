<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\StockAlert;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio para manejar movimientos de inventario y alertas.
 */
class ServicioInventario
{
    /**
     * Registra un movimiento de inventario, actualiza stock y verifica alertas.
     * Comentarios en español, código en inglés.
     *
     * @param Product $product
     * @param string $type (entrada|salida|ajuste|reserva|liberacion)
     * @param int $qty
     * @param string|null $ref
     * @param string|null $notes
     * @param \App\Models\User|null $user
     * @return InventoryMovement
     */
    public function registrarMovimiento(Product $product, string $type, int $qty, ?string $ref = null, ?string $notes = null, $user = null): InventoryMovement
    {
        $userId = $user ? $user->id : (Auth::check() ? Auth::id() : null);
        $old = (int) ($product->stock ?? 0);

        if ($type === 'ajuste') {
            // For 'ajuste', $qty represents the target stock value
            $new = (int) $qty;
            $cantidad = abs($new - $old);
        } else {
            if (in_array($type, ['entrada', 'liberacion'])) {
                $new = $old + $qty;
            } else { // salida, reserva
                $new = max(0, $old - $qty);
            }
            $cantidad = $qty;
        }

        $movement = InventoryMovement::create([
            'producto_id' => $product->id,
            'usuario_id' => $userId,
            'tipo' => $type,
            'cantidad' => $cantidad,
            'cantidad_anterior' => $old,
            'cantidad_nueva' => $new,
            'referencia' => $ref,
            'notas' => $notes,
        ]);

        $product->stock = $new;
        $product->save();

        $this->verificarAlertaStock($product);

        return $movement;
    }

    /**
     * Verifica y crea/actualiza alertas de stock para un producto.
     * - low_stock cuando stock <= stock_minimo
     * - sin_stock cuando stock == 0
     * Marca como leídas las alertas si stock > stock_minimo
     *
     * @param Product $product
     * @return void
     */
    public function verificarAlertaStock(Product $product): void
    {
        if ($product->stock <= $product->stock_minimo) {
            StockAlert::updateOrCreate(
                ['producto_id' => $product->id, 'tipo' => 'low_stock'],
                ['leido' => false]
            );
        }

        if ($product->stock === 0) {
            StockAlert::updateOrCreate(
                ['producto_id' => $product->id, 'tipo' => 'sin_stock'],
                ['leido' => false]
            );
        }

        if ($product->stock > $product->stock_minimo) {
            StockAlert::where('producto_id', $product->id)
                ->whereIn('tipo', ['low_stock', 'sin_stock'])
                ->update(['leido' => true]);
        }
    }

    /**
     * Ajusta el stock a un nuevo valor y registra el movimiento tipo 'ajuste'.
     *
     * @param Product $product
     * @param int $newStock
     * @param string $reason
     * @param \App\Models\User|null $user
     * @return void
     */
    public function ajustarStock(Product $product, int $newStock, string $reason, $user = null): void
    {
        $old = (int) ($product->stock ?? 0);
        if ($newStock === $old) {
            return;
        }

        // Registrar como 'ajuste' pasando el nuevo stock como cantidad (ver registrarMovimiento)
        $this->registrarMovimiento($product, 'ajuste', $newStock, null, $reason, $user);
    }
}
