<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            // Mínimo de compra al por mayor expresado en USD
            $table->decimal('minimo_compra_mayor_usd', 15, 2)->default(50.00)->comment('Monto mínimo en USD para ventas al mayor');
            // Mínimo de unidades totales para considerar venta al mayor
            $table->integer('minimo_unidades_mayor')->default(10)->comment('Cantidad mínima de unidades para venta al mayor');
            // Tasas de cambio administrables
            $table->decimal('tasa_bcv', 18, 6)->nullable()->comment('Tasa BCV Bs/USD');
            $table->decimal('tasa_binance', 18, 6)->nullable()->comment('Tasa Binance USDT');

            $table->unsignedBigInteger('updated_by')->nullable()->comment('Usuario que actualizó la configuración por última vez');

            $table->timestamps();
        });

        // Insertar valores por defecto (una sola fila)
        DB::table('configuraciones')->insert([
            'minimo_compra_mayor_usd' => 50.00,
            'minimo_unidades_mayor' => 10,
            'tasa_bcv' => null,
            'tasa_binance' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
    }
};
