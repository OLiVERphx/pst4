<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla: cart_items - líneas de carrito persistente
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id')->comment('Referencia al carrito');
            $table->unsignedBigInteger('producto_id')->comment('Referencia al producto');
            $table->integer('cantidad')->default(1)->comment('Cantidad solicitada');
            $table->enum('tipo', ['detal','mayor'])->default('detal')->comment('Tipo de precio aplicado');
            $table->decimal('precio_unitario', 12, 2)->nullable()->comment('Precio capturado en el momento (cache)');
            $table->timestamps();

            $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
            $table->index('cart_id');
            $table->index('producto_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cart_items');
    }
};
