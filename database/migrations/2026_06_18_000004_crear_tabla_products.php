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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 200);
            $table->string('slug', 200)->unique()->nullable();
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('marca_id');
            $table->unsignedBigInteger('categoria_id');
            $table->decimal('precio_detal', 10, 2);
            $table->decimal('precio_mayor', 10, 2);
            $table->decimal('precio_costo', 10, 2)->nullable();
            $table->integer('min_cantidad_mayor')->default(10);
            $table->integer('stock')->default(0);
            $table->integer('stock_minimo')->default(5);
            $table->json('imagenes')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('destacado')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('marca_id')->references('id')->on('marcas');
            $table->foreign('categoria_id')->references('id')->on('categorias');

            $table->index('categoria_id');
            $table->index('marca_id');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};