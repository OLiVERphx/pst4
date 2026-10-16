<?php

namespace App\Console\Commands;

use App\Services\ServicioPrecios;
use Illuminate\Console\Command;

class ActualizarTasaBcv extends Command
{
    protected $signature = 'tasa:actualizar';
    protected $description = 'Consulta la tasa oficial BCV (DolarApi) y la guarda en el historial tasas_bcv';

    public function handle(): int
    {
        $tasa = ServicioPrecios::actualizarTasaBcvDesdeBcv();

        if ($tasa === null) {
            $this->error('No se pudo obtener la tasa BCV. Revisa storage/logs/laravel.log.');
            return self::FAILURE;
        }

        $this->info("Tasa BCV registrada: {$tasa} Bs/USD");
        return self::SUCCESS;
    }
}
