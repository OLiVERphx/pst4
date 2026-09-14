<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Category (categorias).
 */
class Category extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    protected $fillable = [
        'padre_id',
        'nombre',
        'slug',
        'icono',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'padre_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'padre_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'categoria_id');
    }

    public function scopeActivo($q)
    {
        return $q->where('activo', true);
    }
}
