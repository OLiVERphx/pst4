<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos para validar pagos en bolívares (Pago Móvil / transferencia):
 * la tasa BCV usada, el monto esperado en Bs y los datos del pagador.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->decimal('tasa_bcv_aplicada', 18, 6)->nullable()->after('fecha_pago_declarada')->comment('Tasa BCV vigente en la fecha del pago');
            $table->decimal('monto_bs_esperado', 14, 2)->nullable()->after('tasa_bcv_aplicada')->comment('monto USD × tasa_bcv_aplicada');
            $table->string('banco_origen', 60)->nullable()->after('monto_bs_esperado');
            $table->string('cedula_pagador', 15)->nullable()->after('banco_origen');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn(['tasa_bcv_aplicada', 'monto_bs_esperado', 'banco_origen', 'cedula_pagador']);
        });
    }
};
