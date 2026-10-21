<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Captcha de Cloudflare Turnstile.
 *
 * - Sin TURNSTILE_SECRET_KEY el captcha está apagado y la regla no hace nada
 *   (tests, desarrollo local y demostración).
 * - Si Cloudflare no responde se deja pasar y se anota en el registro: una caída
 *   de un tercero no debe impedir comprar ni entrar. Con TURNSTILE_FAIL_CLOSED=true
 *   se bloquea en su lugar.
 */
class Turnstile implements ValidationRule
{
    /** Se evalúa aunque el campo venga vacío: un bot simplemente no lo envía. */
    public bool $implicit = true;

    public static function activo(): bool
    {
        return filled(config('services.turnstile.secret_key'));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::activo()) {
            return;
        }

        $mensaje = 'No pudimos verificar que eres una persona. Inténtalo de nuevo.';

        if (! is_string($value) || $value === '' || strlen($value) > 2048) {
            $fail($mensaje);
            return;
        }

        try {
            $respuesta = Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);
        } catch (Throwable $e) {
            $this->cloudflareNoResponde($e->getMessage(), $fail, $mensaje);
            return;
        }

        if ($respuesta->serverError()) {
            $this->cloudflareNoResponde('HTTP '.$respuesta->status(), $fail, $mensaje);
            return;
        }

        if ($respuesta->json('success') !== true) {
            $fail($mensaje);
        }
    }

    private function cloudflareNoResponde(string $motivo, Closure $fail, string $mensaje): void
    {
        Log::warning('Turnstile no respondió: '.$motivo);

        if (config('services.turnstile.fail_closed')) {
            $fail($mensaje);
        }
    }
}
