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
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('padre_id')->nullable();
            $table->string('nombre', 80);
            $table->string('slug', 80)->unique();
            $table->string('icono', 30)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('padre_id')->references('id')->on('categorias')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};