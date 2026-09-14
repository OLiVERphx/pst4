<?php

namespace App\Services;

use App\Models\Product;
use App\Models\InventoryMovement;
use App\Models\StockAlert;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Servicio de inventario: registrar movimientos y gestionar alertas de stock.
 */
class ServicioInventario
{
    /**
     * Registra un movimiento de inventario y actualiza el stock del producto con bloqueo de fila y auditoría.
     *
     * @param Product $p
     * @param string $type (entrada|salida|ajuste|reserva|liberacion)
     * @param int $qty
     * @param string|null $ref
     * @param string|null $notes
     * @param User|null $user
     * @return InventoryMovement
     */
    public function registrarMovimiento(Product $p, string $type, int $qty, ?string $ref = null, ?string $notes = null, ?User $user = null): InventoryMovement
    {
        return DB::transaction(function () use ($p, $type, $qty, $ref, $notes, $user) {
            $lockedProduct = Product::where('id', $p->id)->lockForUpdate()->first() ?? $p;
            $cantidadAnterior = (int) $lockedProduct->stock;

            // Determinar nuevo stock según el tipo
            $nueva = $cantidadAnterior;
            if (in_array($type, ['entrada', 'liberacion'])) {
                $nueva += $qty;
            } elseif (in_array($type, ['salida', 'reserva'])) {
                $nueva -= $qty;
            } elseif ($type === 'ajuste') {
                // qty es la diferencia (puede ser negativa)
                $nueva += $qty;
            } else {
                // fallback: sumar
                $nueva += $qty;
            }

            if ($nueva < 0) {
                $nueva = 0;
            }

            // Guardar nuevo stock
            $lockedProduct->stock = $nueva;
            $lockedProduct->save();

            $p->stock = $nueva;

            $userId = $user?->id ?? (Auth::check() ? Auth::id() : null);

            // Crear registro de movimiento
            $movement = InventoryMovement::create([
                'producto_id' => $lockedProduct->id,
                'usuario_id' => $userId,
                'tipo' => $type,
                'cantidad' => $qty,
                'cantidad_anterior' => $cantidadAnterior,
                'cantidad_nueva' => $nueva,
                'referencia' => $ref,
                'notas' => $notes,
            ]);

            // Registrar en bitácora de auditoría
            ServicioAuditoria::registrar(
                'inventario.movimiento',
                $lockedProduct,
                ['stock' => $cantidadAnterior],
                ['stock' => $nueva, 'tipo' => $type, 'cantidad' => $qty, 'referencia' => $ref, 'notas' => $notes],
                $userId
            );

            // Verificar alertas
            $this->verificarAlertaStock($lockedProduct);

            return $movement;
        });
    }

    /**
     * Verifica y crea/actualiza alertas de stock para un producto.
     *
     * @param Product $product
     * @return void
     */
    public function verificarAlertaStock(Product $product): void
    {
        $product->refresh();

        if ($product->stock <= 0) {
            // Crear alerta sin_stock (no leída)
            StockAlert::updateOrCreate(
                ['producto_id' => $product->id, 'tipo' => 'sin_stock'],
                ['leido' => false]
            );
            // También mantener low_stock como no leída si aplica
            StockAlert::updateOrCreate(
                ['producto_id' => $product->id, 'tipo' => 'low_stock'],
                ['leido' => false]
            );
        } elseif ($product->stock <= $product->stock_minimo) {
            // low_stock
            StockAlert::updateOrCreate(
                ['producto_id' => $product->id, 'tipo' => 'low_stock'],
                ['leido' => false]
            );
            // marcar sin_stock (si existe) como leída
            StockAlert::where('producto_id', $product->id)
                ->where('tipo', 'sin_stock')
                ->update(['leido' => true]);
        } else {
            // stock suficiente: marcar alertas existentes como leídas
            StockAlert::where('producto_id', $product->id)
                ->whereIn('tipo', ['low_stock', 'sin_stock'])
                ->update(['leido' => true]);
        }
    }

    /**
     * Ajusta el stock de un producto a un nuevo valor y registra el movimiento y la auditoría.
     *
     * @param Product $p
     * @param int $newStock
     * @param string $reason
     * @param User|null $user
     * @return void
     */
    public function ajustarStock(Product $p, int $newStock, string $reason, ?User $user = null): void
    {
        $p->refresh();
        $old = (int) $p->stock;
        $diff = $newStock - $old;

        // Registrar movimiento tipo ajuste con la diferencia
        $this->registrarMovimiento($p, 'ajuste', $diff, null, $reason, $user);

        // Registro explícito de auditoría de ajuste manual
        ServicioAuditoria::registrar(
            'inventario.ajustado',
            $p,
            ['stock' => $old],
            ['stock' => $newStock, 'diferencia' => $diff, 'motivo' => $reason],
            $user?->id
        );
    }
}
