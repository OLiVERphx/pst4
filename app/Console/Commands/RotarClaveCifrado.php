<?php

namespace App\Console\Commands;

use App\Services\ServicioAuditoria;
use App\Support\IndiceCiego;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Rotación de APP_KEY (por ejemplo, si las variables de entorno quedaron expuestas).
 *
 * Con la clave nueva en APP_KEY y la anterior en APP_PREVIOUS_KEYS, vuelve a cifrar los datos
 * personales y los secretos de 2FA con la clave nueva y recalcula los índices ciegos de cédula y
 * teléfono (dependen de APP_KEY; sin recalcularlos, la búsqueda por cédula deja de funcionar).
 */
class RotarClaveCifrado extends Command
{
    protected $signature = 'seguridad:rotar-clave {--simular : Solo cuenta lo que cambiaría, sin guardar}';

    protected $description = 'Vuelve a cifrar los datos con la APP_KEY nueva (la anterior va en APP_PREVIOUS_KEYS)';

    /** tabla => columnas cifradas (los casts 'encrypted' y 'encrypted:array' usan encryptString) */
    private const COLUMNAS = [
        'users' => ['cedula', 'telefono', 'direccion', 'two_factor_secret', 'two_factor_recovery_codes'],
        'pedidos' => ['entrega_telefono', 'entrega_direccion'],
        'pagos' => ['cedula_pagador', 'metadata_comprobante'],
    ];

    public function handle(): int
    {
        if (empty(array_filter(config('app.previous_keys', [])))) {
            $this->error('Falta APP_PREVIOUS_KEYS con la clave anterior: sin ella no se pueden leer los datos actuales.');
            return self::FAILURE;
        }

        $simular = (bool) $this->option('simular');
        $total = 0;
        $ilegibles = 0;

        DB::transaction(function () use ($simular, &$total, &$ilegibles) {
            foreach (self::COLUMNAS as $tabla => $columnas) {
                DB::table($tabla)->select(array_merge(['id'], $columnas))->orderBy('id')
                    ->chunkById(200, function ($filas) use ($tabla, $columnas, $simular, &$total, &$ilegibles) {
                        foreach ($filas as $fila) {
                            $cambios = [];
                            foreach ($columnas as $columna) {
                                if ($fila->{$columna} === null || $fila->{$columna} === '') {
                                    continue;
                                }
                                try {
                                    // Crypt prueba APP_KEY y luego APP_PREVIOUS_KEYS
                                    $plano = Crypt::decryptString($fila->{$columna});
                                } catch (DecryptException) {
                                    $ilegibles++;
                                    $this->warn("{$tabla}#{$fila->id}.{$columna}: no se pudo descifrar con ninguna clave.");
                                    continue;
                                }
                                $cambios[$columna] = Crypt::encryptString($plano);
                                if ($tabla === 'users' && $columna === 'cedula') {
                                    $cambios['cedula_hash'] = IndiceCiego::cedula($plano);
                                }
                                if ($tabla === 'users' && $columna === 'telefono') {
                                    $cambios['telefono_hash'] = IndiceCiego::telefono($plano);
                                }
                            }
                            if ($cambios) {
                                $total++;
                                if (! $simular) {
                                    DB::table($tabla)->where('id', $fila->id)->update($cambios);
                                }
                            }
                        }
                    });
            }

            if ($ilegibles > 0 && ! $simular) {
                // Mejor no dejar la base a medias: se revierte todo y se avisa
                throw new \RuntimeException("Hay {$ilegibles} valores que no se pudieron descifrar; no se cambió nada.");
            }
        });

        if ($simular) {
            $this->info("Simulación: se volverían a cifrar {$total} registros. No se guardó nada.");
            return $ilegibles ? self::FAILURE : self::SUCCESS;
        }

        // Las sesiones abiertas se firmaron con la clave anterior
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->delete();
        }
        ServicioAuditoria::registrar('seguridad.clave_rotada', null, null, ['registros' => $total], null);

        $this->info("Listo: {$total} registros cifrados con la clave nueva. Ya puedes quitar APP_PREVIOUS_KEYS.");
        $this->line('Guarda la clave anterior fuera del servidor: los respaldos viejos se cifraron con ella.');

        return self::SUCCESS;
    }
}
