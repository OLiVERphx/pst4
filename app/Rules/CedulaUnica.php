<?php

namespace App\Rules;

use App\Support\IndiceCiego;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * La cédula se guarda cifrada, así que "unique:users,cedula" ya no sirve:
 * la unicidad se comprueba sobre su índice ciego (users.cedula_hash).
 */
class CedulaUnica implements ValidationRule
{
    public function __construct(private ?int $ignorarUsuarioId = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $hash = IndiceCiego::cedula(is_scalar($value) ? (string) $value : null);
        if ($hash === null) {
            return;
        }

        $existe = DB::table('users')
            ->where('cedula_hash', $hash)
            ->when($this->ignorarUsuarioId, fn ($q) => $q->where('id', '!=', $this->ignorarUsuarioId))
            ->exists();

        if ($existe) {
            $fail('Esta cédula ya está registrada.');
        }
    }
}
