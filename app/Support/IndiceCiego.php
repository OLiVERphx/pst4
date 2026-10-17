<?php

namespace App\Support;

/**
 * Índice ciego para datos personales cifrados.
 *
 * La cédula y el teléfono se guardan cifrados (no se pueden buscar con SQL),
 * así que junto a cada uno se guarda una huella HMAC-SHA256 del valor
 * normalizado. Con ella se busca por valor exacto y se garantiza unicidad,
 * pero no se puede recuperar el dato original: quien lea la base solo ve la
 * huella. La clave se deriva de APP_KEY.
 */
class IndiceCiego
{
    /** "V-12.345.678", "v12345678" y "12345678" dan la misma huella. */
    public static function cedula(?string $valor): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $valor);

        return $digitos === '' ? null : self::huella('cedula', $digitos);
    }

    /** "0414-1234567", "04141234567" y "+58 414 1234567" dan la misma huella. */
    public static function telefono(?string $valor): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $valor);
        if (strlen($digitos) === 12 && str_starts_with($digitos, '58')) {
            $digitos = '0' . substr($digitos, 2);
        } elseif (strlen($digitos) === 10 && ! str_starts_with($digitos, '0')) {
            $digitos = '0' . $digitos;
        }

        return $digitos === '' ? null : self::huella('telefono', $digitos);
    }

    private static function huella(string $tipo, string $valor): string
    {
        return hash_hmac('sha256', $tipo . '|' . $valor, self::clave());
    }

    private static function clave(): string
    {
        $appKey = (string) config('app.key');
        if (str_starts_with($appKey, 'base64:')) {
            $appKey = base64_decode(substr($appKey, 7));
        }

        // Subclave propia para no usar APP_KEY tal cual en dos propósitos
        return hash_hmac('sha256', 'indice-ciego', $appKey, true);
    }
}
