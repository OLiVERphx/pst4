<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo OrderItem (items_pedido).
 */
class OrderItem extends Model
{
    use HasFactory;

    // Este modelo guarda filas en items_pedido que no tienen columnas de timestamps
    public $timestamps = false;

    protected $table = 'items_pedido';

    protected $fillable = [
        'pedido_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'pedido_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }
}
