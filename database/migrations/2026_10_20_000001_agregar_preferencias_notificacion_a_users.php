<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qué avisos quiere recibir cada usuario del panel (campana y reporte semanal).
 * NULL = todos activos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('preferencias_notificacion')->nullable()->comment('tipo => bool, ver User::NOTIFICACIONES');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferencias_notificacion');
        });
    }
};
