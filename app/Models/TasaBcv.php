<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Modelo TasaBcv (tasas_bcv): historial diario de la tasa oficial Bs/USD.
 */
class TasaBcv extends Model
{
    protected $table = 'tasas_bcv';

    protected $fillable = ['fecha', 'tasa', 'fuente', 'registrado_por'];

    protected $casts = [
        'fecha' => 'date',
        'tasa' => 'decimal:6',
    ];

    /**
     * Tasa vigente en una fecha: la última registrada en o antes de ese día
     * (cubre fines de semana y feriados, cuando el BCV no publica).
     */
    public static function vigenteEn(CarbonInterface|string|null $fecha = null): ?self
    {
        $dia = Carbon::parse($fecha ?? now())->toDateString();

        return self::whereDate('fecha', '<=', $dia)->orderByDesc('fecha')->first();
    }
}
