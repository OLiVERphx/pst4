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
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->enum('tipo', ['entrada', 'salida', 'ajuste', 'reserva', 'liberacion']);
            $table->integer('cantidad');
            $table->integer('cantidad_anterior');
            $table->integer('cantidad_nueva');
            $table->string('referencia', 100)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->foreign('producto_id')->references('id')->on('products');
            $table->foreign('usuario_id')->references('id')->on('users');

            $table->index('producto_id');
            $table->index('tipo');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};