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
            $table->foreignId('usuario_id')->constrained('users');
            $table->enum('tipo', ['detal', 'mayor'])->default('detal');
            $table->enum('estado', ['pendiente', 'pago_subido', 'pago_verificado', 'procesando', 'enviado', 'entregado', 'cancelado'])->default('pendiente');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->text('entrega_nombre');
            $table->text('entrega_telefono');
            $table->text('entrega_direccion');
            $table->text('entrega_ciudad');
            $table->text('entrega_notas')->nullable();
            $table->foreignId('confirmado_por')->nullable()->constrained('users');
            $table->dateTime('confirmado_en')->nullable();
            $table->foreignId('cancelado_por')->nullable()->constrained('users');
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

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

