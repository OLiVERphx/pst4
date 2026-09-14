<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

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
        'confirmado_por',
        'confirmado_en',
        'cancelado_por',
        'motivo_cancelacion',
    ];

    protected $casts = [
        'tipo' => 'string',
        'estado' => 'string',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'pedido_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'pedido_id');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmado_por');
    }

    public function scopePendiente($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeEsperandoVerificacion($query)
    {
        return $query->where('estado', 'pago_subido');
    }

    public static function generarNumeroPedido(): string
    {
        return sprintf('SW-2026-%04d', random_int(1, 9999));
    }
}

