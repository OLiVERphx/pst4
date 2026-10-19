<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos de la empresa y de cobro (transferencia, pago móvil, Binance) editables
 * desde Configuración. Antes estaban escritos a mano en las vistas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuraciones', function (Blueprint $table) {
            $table->json('datos_empresa')->nullable()->comment('nombre, rif, telefono, direccion');
            $table->json('datos_pago')->nullable()->comment('transferencia, pagomovil, binance');
        });
    }

    public function down(): void
    {
        Schema::table('configuraciones', function (Blueprint $table) {
            $table->dropColumn(['datos_empresa', 'datos_pago']);
        });
    }
};
