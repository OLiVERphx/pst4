<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Events\ProductTrackingEvent;
use Illuminate\Support\Facades\Auth;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

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

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'marca_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'categoria_id', 'id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'producto_id', 'id');
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class, 'producto_id', 'id');
    }

    public function stockAlerts()
    {
        return $this->hasMany(StockAlert::class, 'producto_id', 'id');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeDestacado($query)
    {
        return $query->where('destacado', true);
    }

    public function scopeStockBajo($query)
    {
        return $query->whereColumn('stock', '<=', 'stock_minimo');
    }

    public function getMargenGananciaAttribute()
    {
        if (!$this->precio_detal || $this->precio_detal == 0) {
            return 0;
        }

        return ($this->precio_detal - $this->precio_costo) / $this->precio_detal * 100;
    }
    public static function boot()
    {
        parent::boot();

        /* self::creating(function($model){
            // ... code here
        });

        self::created(function($model){
            // ... code here
        });*/

        /* self::updating(function($model){
            dd('Updating '.$model->id);
        }); */

        self::updated(function($model){
            $user = Auth::check() ? Auth::user() : null;
            ProductTrackingEvent::dispatch($model, $user);
        });

        /* self::deleting(function($model){
            // ... code here
        });

        self::deleted(function($model){
            // ... code here
        }); */
    }
}
