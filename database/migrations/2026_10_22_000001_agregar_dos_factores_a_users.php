<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verificación en dos pasos (TOTP, apps tipo Google Authenticator) para el panel.
 * El secreto se guarda cifrado con APP_KEY; los códigos de recuperación, solo su hash.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->comment('Secreto TOTP cifrado');
            $table->text('two_factor_recovery_codes')->nullable()->comment('Hashes de los códigos de recuperación (JSON cifrado)');
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->unsignedBigInteger('two_factor_ultimo_paso')->nullable()->comment('Último intervalo TOTP aceptado: impide reusar un código');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'two_factor_ultimo_paso']);
        });
    }
};
