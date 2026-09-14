<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Product (products).
 */
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo',
        'nombre',
        'slug',
        'descripcion',
        'marca_id',
        'categoria_id',
        'precio_detal',
        'precio_mayor',
        'precio_costo',
        'min_cantidad_mayor',
        'stock',
        'stock_minimo',
        'imagenes',
        'activo',
        'destacado',
    ];

    protected $casts = [
        'precio_detal' => 'decimal:2',
        'precio_mayor' => 'decimal:2',
        'precio_costo' => 'decimal:2',
        'imagenes' => 'array',
        'activo' => 'boolean',
        'destacado' => 'boolean',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'marca_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'producto_id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'producto_id');
    }

    public function stockAlerts(): HasMany
    {
        return $this->hasMany(StockAlert::class, 'producto_id');
    }

    public function scopeActivo($q)
    {
        return $q->where('activo', true);
    }

    public function scopeDestacado($q)
    {
        return $q->where('destacado', true);
    }

    public function scopeStockBajo($q)
    {
        return $q->whereColumn('stock', '<=', 'stock_minimo');
    }

    // Accesor: margen de ganancia en porcentaje
    public function getMargenGananciaAttribute()
    {
        if ($this->precio_detal && $this->precio_costo !== null && $this->precio_detal != 0) {
            return round((($this->precio_detal - $this->precio_costo) / $this->precio_detal) * 100, 2);
        }
        return null;
    }

    /**
     * Observer simple para invalidar el índice TF-IDF en cache cuando un producto
     * se crea/actualiza/elimina. La reconstrucción se hace por el comando programado
     * o manualmente con php artisan tfidf:build.
     */
    protected static function booted()
    {
        parent::booted();

        static::saved(function ($product) {
            \Illuminate\Support\Facades\Cache::forget('tfidf_index_products_v1');
        });

        static::deleted(function ($product) {
            \Illuminate\Support\Facades\Cache::forget('tfidf_index_products_v1');
        });
    }
}
