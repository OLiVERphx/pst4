<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Payment (pagos).
 */
class Payment extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'pedido_id',
        'metodo',
        // monto es el monto calculado por el servidor (total del pedido)
        'monto',
        // monto_declarado, moneda_declarada y fecha_pago_declarada vienen del cliente y se usan solo para verificación
        'monto_declarado',
        'moneda_declarada',
        'fecha_pago_declarada',
        'moneda',
        'numero_referencia',
        'ruta_comprobante',
        'hash_comprobante',
        'metadata_comprobante',
        'estado',
        'verificado_por',
        'verificado_en',
        'motivo_rechazo',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'monto_declarado' => 'decimal:2',
        'metadata_comprobante' => 'array',
        'fecha_pago_declarada' => 'datetime',
        'verificado_en' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'pedido_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por');
    }

    public function scopePendiente($q)
    {
        return $q->where('estado', 'pendiente');
    }

    public function scopeSospechoso($q)
    {
        return $q->where('estado', 'sospechoso');
    }
}
