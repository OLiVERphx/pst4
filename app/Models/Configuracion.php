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
        'datos_empresa',
        'datos_pago',
        'updated_by',
    ];

    protected $casts = [
        'minimo_compra_mayor_usd' => 'decimal:2',
        'tasa_bcv' => 'decimal:6',
        'tasa_binance' => 'decimal:6',
        'datos_empresa' => 'array',
        'datos_pago' => 'array',
    ];

    /**
     * Devuelve la instancia única de configuración (singleton lógico).
     */
    public static function instance(): ?self
    {
        return self::first();
    }

    /** Datos de la empresa con todas las claves presentes (vacías si no se han cargado). */
    public static function empresa(): array
    {
        return array_merge(
            ['nombre' => '', 'rif' => '', 'telefono' => '', 'direccion' => ''],
            self::instance()?->datos_empresa ?? []
        );
    }

    /** Datos de cobro de la tienda, con todas las claves presentes. */
    public static function datosPago(): array
    {
        $guardado = self::instance()?->datos_pago ?? [];
        $base = [
            'transferencia' => ['banco' => '', 'tipo_cuenta' => 'corriente', 'numero_cuenta' => '', 'titular' => '', 'documento' => ''],
            'pagomovil' => ['banco' => '', 'telefono' => '', 'documento' => ''],
            'binance' => ['pay_id' => '', 'correo' => ''],
        ];
        foreach ($base as $metodo => $campos) {
            $base[$metodo] = array_merge($campos, $guardado[$metodo] ?? []);
        }

        return $base;
    }

    /**
     * Métodos de pago que el cliente puede elegir: los online solo si la tienda
     * cargó los datos mínimos para recibir el pago. El efectivo en tienda siempre.
     */
    public static function metodosPagoDisponibles(): array
    {
        $d = self::datosPago();

        return [
            'transferencia' => $d['transferencia']['banco'] !== '' && $d['transferencia']['numero_cuenta'] !== '',
            'pagomovil' => $d['pagomovil']['banco'] !== '' && $d['pagomovil']['telefono'] !== '',
            'binance' => $d['binance']['pay_id'] !== '',
            'fisico' => true,
        ];
    }
}
