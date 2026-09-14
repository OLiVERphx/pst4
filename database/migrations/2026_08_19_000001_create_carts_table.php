<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla: carts - Carritos persistentes (invitado via token o usuario asociado)
     * columnas en español y comentarios en español donde aplica.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable()->comment('Referencia al usuario si está logueado');
            $table->string('token')->nullable()->unique()->comment('Token de sesión para invitados');
            $table->enum('estado', ['active', 'reserved', 'checked_out'])->default('active')->comment('Estado del carrito');
            $table->timestamp('reserved_at')->nullable()->comment('Marca temporal de cuando se realizó la reserva temporal de stock');
            $table->timestamps();
            $table->softDeletes();

            $table->index('usuario_id');
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('carts');
    }
};
