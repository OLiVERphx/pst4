<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $table = 'cart_items';

    protected $fillable = [
        'cart_id', 'producto_id', 'cantidad', 'tipo', 'precio_unitario'
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    public function producto()
    {
        return $this->belongsTo(\App\Models\Product::class, 'producto_id');
    }
}
