<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Brand (marcas).
 */
class Brand extends Model
{
    use HasFactory;

    protected $table = 'marcas';

    protected $fillable = [
        'nombre',
        'logo',
    ];

    // Relación: marca -> productos
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'marca_id');
    }
}
