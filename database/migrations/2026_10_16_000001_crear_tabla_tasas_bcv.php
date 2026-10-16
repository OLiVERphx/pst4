<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial diario de la tasa oficial BCV (Bs por USD).
 * Permite validar un pago en bolívares con la tasa vigente en la fecha del pago,
 * no con la tasa de hoy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasas_bcv', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique()->comment('Fecha a partir de la cual rige la tasa');
            $table->decimal('tasa', 18, 6)->comment('Bs por 1 USD');
            $table->string('fuente', 30)->default('manual')->comment('dolarapi | manual');
            $table->unsignedBigInteger('registrado_por')->nullable();
            $table->timestamps();

            $table->foreign('registrado_por')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasas_bcv');
    }
};
