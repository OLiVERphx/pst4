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
            $table->foreignId('producto_id')->constrained('products');
            $table->foreignId('usuario_id')->nullable()->constrained('users');
            $table->enum('tipo', ['entrada', 'salida', 'ajuste', 'reserva', 'liberacion']);
            $table->integer('cantidad');
            $table->integer('cantidad_anterior');
            $table->integer('cantidad_nueva');
            $table->string('referencia', 100)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

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
