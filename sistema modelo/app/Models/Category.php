<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'slug',
        'icono',
        'padre_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'padre_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'padre_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'categoria_id');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }
}
