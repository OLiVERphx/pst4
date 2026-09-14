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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos');
            $table->enum('metodo', ['transferencia', 'pagomovil', 'binance', 'fisico']);
            $table->decimal('monto', 10, 2);
            $table->string('moneda', 5)->default('USD');
            $table->string('numero_referencia', 100)->nullable();
            $table->string('ruta_comprobante', 500)->nullable();
            $table->string('hash_comprobante', 64)->nullable();
            $table->json('metadata_comprobante')->nullable();
            $table->enum('estado', ['pending', 'valid', 'invalid', 'suspicious'])->default('pending');
            $table->foreignId('verificado_por')->nullable()->constrained('users');
            $table->dateTime('verificado_en')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->timestamps();

            $table->index('pedido_id');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
