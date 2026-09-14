<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->text('entrega_nombre')->nullable()->change();
            $table->text('entrega_telefono')->nullable()->change();
            $table->text('entrega_direccion')->nullable()->change();
            $table->text('entrega_ciudad')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->text('entrega_nombre')->nullable(false)->change();
            $table->text('entrega_telefono')->nullable(false)->change();
            $table->text('entrega_direccion')->nullable(false)->change();
            $table->text('entrega_ciudad')->nullable(false)->change();
        });
    }
};
