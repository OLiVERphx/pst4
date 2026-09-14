<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'carts';

    // Comentarios en español: relaciones y helper
    protected $fillable = [
        'usuario_id', 'token', 'estado', 'reserved_at'
    ];

    protected $dates = ['reserved_at'];

    public function items()
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'usuario_id');
    }
}
