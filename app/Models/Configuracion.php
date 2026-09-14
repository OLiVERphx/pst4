<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Configuracion (configuraciones).
 * Guarda parámetros globales editables sólo por usuarios con permiso 'configuracion.editar'.
 */
class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = [
        'minimo_compra_mayor_usd',
        'minimo_unidades_mayor',
        'tasa_bcv',
        'tasa_binance',
        'updated_by',
    ];

    protected $casts = [
        'minimo_compra_mayor_usd' => 'decimal:2',
        'tasa_bcv' => 'decimal:6',
        'tasa_binance' => 'decimal:6',
    ];

    /**
     * Devuelve la instancia única de configuración (singleton lógico).
     */
    public static function instance(): self
    {
        return self::first();
    }
}
