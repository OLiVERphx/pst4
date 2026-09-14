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
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->unsignedBigInteger('usuario_id')->nullable()->change();
            $table->foreign('usuario_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->unsignedBigInteger('usuario_id')->nullable(false)->change();
            $table->foreign('usuario_id')->references('id')->on('users');
        });
    }
};
