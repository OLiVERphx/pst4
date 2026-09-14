<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla bitacora_auditoria.
 * Registro inmutable (append-only) de acciones sensibles del sistema.
 */
return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     */
    public function up(): void
    {
        Schema::create('bitacora_auditoria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable()->comment('Usuario que ejecutó la acción, nulo si es acción del sistema o invitado');
            $table->string('accion', 100)->comment('Identificador de la acción realizada');
            $table->string('entidad_tipo', 255)->nullable()->comment('Nombre de clase o tipo de la entidad afectada');
            $table->unsignedBigInteger('entidad_id')->nullable()->comment('ID de la entidad afectada');
            $table->json('valores_antes')->nullable()->comment('Estado o valores previos a la acción');
            $table->json('valores_despues')->nullable()->comment('Estado o valores posteriores a la acción');
            $table->string('ip_origen', 45)->nullable()->comment('Dirección IP de origen de la solicitud');
            $table->text('user_agent')->nullable()->comment('Agente de usuario del navegador o cliente');
            $table->timestamp('created_at')->useCurrent()->comment('Fecha y hora del registro de auditoría');

            // Clave foránea e índices de optimización para búsquedas y filtros
            $table->foreign('usuario_id')->references('id')->on('users')->nullOnDelete();
            $table->index('usuario_id');
            $table->index('accion');
            $table->index(['entidad_tipo', 'entidad_id']);
            $table->index('created_at');
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora_auditoria');
    }
};
