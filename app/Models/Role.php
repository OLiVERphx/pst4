<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Role.
 */
class Role extends Model
{
    use HasFactory;

    // El id es tiny integer no auto-incremental
    public $incrementing = false;
    protected $keyType = 'int';

    // Campos asignables (coinciden con columnas de la BD)
    protected $fillable = [
        'nombre',
        'permisos',
    ];

    protected $casts = [
        'permisos' => 'array',
    ];

    // Relaciones
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}
