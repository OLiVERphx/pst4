<?php

use App\Support\IndiceCiego;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cifra en reposo los datos personales (AES-256 con APP_KEY):
 *  - users: cedula, telefono, direccion (+ índices ciegos cedula_hash / telefono_hash)
 *  - pedidos: entrega_telefono, entrega_direccion
 *  - pagos: cedula_pagador, metadata_comprobante (incluye lo leído por OCR)
 *
 * Quien lea la base directamente (HeidiSQL, un respaldo robado) ve texto ilegible.
 * IMPORTANTE: si se pierde APP_KEY, estos datos no se pueden recuperar.
 */
return new class extends Migration
{
    private const COLUMNAS = [
        'users' => ['cedula', 'telefono', 'direccion'],
        'pedidos' => ['entrega_telefono', 'entrega_direccion'],
        'pagos' => ['cedula_pagador', 'metadata_comprobante'],
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['cedula']);
        });
        Schema::table('users', function (Blueprint $table) {
            // El texto cifrado es mucho más largo que el dato original
            $table->text('cedula')->nullable()->change();
            $table->text('telefono')->nullable()->change();
            $table->string('cedula_hash', 64)->nullable()->unique()->after('cedula');
            $table->string('telefono_hash', 64)->nullable()->index()->after('telefono');
        });
        Schema::table('pagos', function (Blueprint $table) {
            $table->text('cedula_pagador')->nullable()->change();
            $table->longText('metadata_comprobante')->nullable()->change();
        });

        foreach (self::COLUMNAS as $tabla => $columnas) {
            DB::table($tabla)->lazyById()->each(function ($fila) use ($tabla, $columnas) {
                $cambios = [];
                foreach ($columnas as $col) {
                    $cambios[$col] = $this->cifrar($fila->{$col});
                }
                if ($tabla === 'users') {
                    $cambios['cedula_hash'] = IndiceCiego::cedula($this->descifrar($fila->cedula));
                    $cambios['telefono_hash'] = IndiceCiego::telefono($this->descifrar($fila->telefono));
                }
                DB::table($tabla)->where('id', $fila->id)->update($cambios);
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNAS as $tabla => $columnas) {
            DB::table($tabla)->lazyById()->each(function ($fila) use ($tabla, $columnas) {
                $cambios = [];
                foreach ($columnas as $col) {
                    $cambios[$col] = $this->descifrar($fila->{$col});
                }
                DB::table($tabla)->where('id', $fila->id)->update($cambios);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['cedula_hash']);
            $table->dropIndex(['telefono_hash']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cedula_hash', 'telefono_hash']);
            $table->string('cedula', 20)->nullable()->unique()->change();
            $table->string('telefono', 20)->nullable()->change();
        });
        Schema::table('pagos', function (Blueprint $table) {
            $table->string('cedula_pagador', 15)->nullable()->change();
            $table->json('metadata_comprobante')->nullable()->change();
        });
    }

    /** Cifra si aún no está cifrado (la migración se puede reintentar sin doble cifrado). */
    private function cifrar(?string $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return $valor;
        }
        try {
            Crypt::decryptString($valor);
            return $valor;
        } catch (DecryptException) {
            return Crypt::encryptString($valor);
        }
    }

    private function descifrar(?string $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return $valor;
        }
        try {
            return Crypt::decryptString($valor);
        } catch (DecryptException) {
            return $valor;
        }
    }
};
