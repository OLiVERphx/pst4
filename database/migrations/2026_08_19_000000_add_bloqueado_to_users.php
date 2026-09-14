<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna 'bloqueado' a la tabla users para soportar bloqueo administrativo
     * Un usuario bloqueado puede ver catálogo pero no completar checkout.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('bloqueado')->default(false)->after('activo')->comment('Indica si el cliente está bloqueado para checkout');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bloqueado');
        });
    }
};
