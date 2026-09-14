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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_pedido', 20)->unique();
            $table->unsignedBigInteger('usuario_id');
            $table->enum('tipo', ['retail', 'wholesale', 'detal', 'mayor'])->default('detal');
            $table->enum('estado', ['pending', 'valid', 'pendiente', 'pago_subido', 'pago_verificado', 'procesando', 'enviado', 'entregado', 'cancelado'])->default('pendiente');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->text('entrega_nombre');
            $table->text('entrega_telefono');
            $table->text('entrega_direccion');
            $table->text('entrega_ciudad');
            $table->text('entrega_notas')->nullable();
            $table->unsignedBigInteger('confirmado_por')->nullable();
            $table->dateTime('confirmado_en')->nullable();
            $table->unsignedBigInteger('cancelado_por')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('usuario_id')->references('id')->on('users');
            $table->foreign('confirmado_por')->references('id')->on('users');
            $table->foreign('cancelado_por')->references('id')->on('users');

            $table->index('usuario_id');
            $table->index('estado');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};