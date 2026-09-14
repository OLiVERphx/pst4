<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

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

    public function product()
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
