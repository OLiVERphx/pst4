<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo InventoryMovement (movimientos_inventario).
 */
class InventoryMovement extends Model
{
    use HasFactory;

    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'producto_id',
        'usuario_id',
        'tipo',
        'cantidad',
        'cantidad_anterior',
        'cantidad_nueva',
        'referencia',
        'notas',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
