<?php

namespace Tests;

use App\Models\Configuracion;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Datos de cobro de prueba: sin ellos el checkout solo ofrece efectivo en tienda.
     */
    protected function configurarDatosDePago(): void
    {
        $config = Configuracion::first() ?? new Configuracion();
        $config->datos_empresa = ['nombre' => 'Tienda de Prueba C.A.', 'rif' => 'J-00000000-0'];
        $config->datos_pago = [
            'transferencia' => ['banco' => '0102', 'tipo_cuenta' => 'corriente', 'numero_cuenta' => '01020000000000000000', 'titular' => 'Tienda de Prueba C.A.', 'documento' => 'J-00000000-0'],
            'pagomovil' => ['banco' => '0102', 'telefono' => '04140000000', 'documento' => 'J-00000000-0'],
            'binance' => ['pay_id' => '123456789'],
        ];
        $config->save();
    }
}
