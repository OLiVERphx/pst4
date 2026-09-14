<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'tipo',
        'leido',
    ];

    protected $casts = [
        'leido' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }

    public function scopeNoLeido($query)
    {
        return $query->where('leido', false);
    }
}
