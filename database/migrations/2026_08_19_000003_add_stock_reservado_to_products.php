<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega stock_reservado a la tabla products para reservas temporales
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'stock_reservado')) {
                $table->integer('stock_reservado')->default(0)->after('stock')->comment('Unidades reservadas temporalmente');
            }
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'stock_reservado')) {
                $table->dropColumn('stock_reservado');
            }
        });
    }
};
