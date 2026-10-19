<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modelo Order (pedidos).
 */
class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pedidos';

    /**
     * Estados en los que el pago del pedido ya está confirmado: cuentan como venta
     * en el dashboard y los reportes. Incluye estados del flujo anterior
     * (pago_verificado, procesando, enviado) para no perder datos históricos.
     */
    public const ESTADOS_PAGADOS = [
        'pago_confirmado', 'pago_confirmado_retirar', 'en_camino', 'entregado', 'completado',
        'pago_verificado', 'procesando', 'enviado',
    ];

    protected $fillable = [
        'numero_pedido',
        'usuario_id',
        'tipo',
        'estado',
        'subtotal',
        'descuento',
        'total',
        'entrega_nombre',
        'entrega_telefono',
        'entrega_direccion',
        'entrega_ciudad',
        'entrega_notas',
        'canal',
        'tipo_entrega',
        'fecha_estimada_entrega',
    ];

    protected $casts = [
        'tipo' => 'string',
        'estado' => 'string',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        // Datos personales de entrega cifrados en la base
        'entrega_telefono' => 'encrypted',
        'entrega_direccion' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'pedido_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'pedido_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmado_por');
    }

    public function scopePendiente($q)
    {
        return $q->where('estado', 'pendiente');
    }

    public function scopeEsperandoVerificacion($q)
    {
        return $q->where('estado', 'pago_subido');
    }

    /**
     * Siguiente número de pedido del año: SW-2026-0001, SW-2026-0002...
     * Antes eran 4 dígitos al azar sin comprobar duplicados: con unos cientos de
     * pedidos al año chocaban y el checkout fallaba. Se bloquea el último número
     * (lockForUpdate) para que dos pedidos simultáneos no tomen el mismo; quien
     * crea el pedido reintenta igualmente si choca (ServicioPedidos).
     */
    public static function generarNumeroPedido(): string
    {
        $prefijo = 'SW-' . date('Y') . '-';

        // Incluye pedidos eliminados (soft delete): su número sigue ocupado
        $ultimo = DB::table('pedidos')
            ->where('numero_pedido', 'like', $prefijo . '%')
            ->orderByRaw('LENGTH(numero_pedido) DESC')
            ->orderByDesc('numero_pedido')
            ->lockForUpdate()
            ->value('numero_pedido');

        $siguiente = $ultimo ? ((int) substr($ultimo, strlen($prefijo))) + 1 : 1;

        return $prefijo . str_pad((string) $siguiente, 4, '0', STR_PAD_LEFT);
    }
}
