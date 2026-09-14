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
        Schema::create('registros_busqueda', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('sesion_id', 100)->nullable();
            $table->string('busqueda', 300);
            $table->integer('total_resultados')->nullable();
            $table->unsignedBigInteger('clicked_producto_id')->nullable();
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('users');
            $table->foreign('clicked_producto_id')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_busqueda');
    }
};