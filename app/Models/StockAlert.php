<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo StockAlert (alertas_stock).
 */
class StockAlert extends Model
{
    use HasFactory;

    protected $table = 'alertas_stock';

    protected $fillable = [
        'producto_id',
        'tipo',
        'leido',
    ];

    protected $casts = [
        'leido' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }

    public function scopeNoLeido($q)
    {
        return $q->where('leido', false);
    }
}
