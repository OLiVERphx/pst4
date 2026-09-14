<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla: audits - registro de auditoría simple para operaciones críticas
     */
    public function up()
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable()->comment('Usuario que realizó la acción');
            $table->string('accion')->comment('Acción realizada (ej. reservar_stock, liberar_reserva, crear_pedido)');
            $table->string('entidad')->nullable()->comment('Entidad afectada (productos, pedidos, cart_items)');
            $table->json('antes')->nullable()->comment('Valores antes de la acción');
            $table->json('despues')->nullable()->comment('Valores después de la acción');
            $table->string('ip')->nullable();
            $table->timestamps();

            $table->index('usuario_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('audits');
    }
};
