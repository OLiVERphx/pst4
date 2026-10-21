<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Verificación en dos pasos con códigos TOTP de 6 dígitos (RFC 6238), compatibles con
 * Google Authenticator, Microsoft Authenticator, Authy, etc.
 *
 * - El secreto se guarda cifrado (cast 'encrypted' de User).
 * - Un código aceptado no se puede volver a usar (two_factor_ultimo_paso).
 * - Los códigos de recuperación se guardan como hash y sirven una sola vez.
 */
class ServicioDosFactores
{
    public const CANTIDAD_CODIGOS = 8;

    /** Intervalos de 30 s aceptados antes y después del actual (desfase del reloj del teléfono). */
    private const VENTANA = 1;

    public function __construct(private Google2FA $google2fa = new Google2FA())
    {
    }

    public function generarSecreto(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function urlOtpauth(User $user, string $secreto): string
    {
        return $this->google2fa->getQRCodeUrl(config('app.name'), $user->email, $secreto);
    }

    public function qrSvg(string $url): string
    {
        $renderer = new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($url);
    }

    /**
     * Para la activación: el código debe corresponder al secreto que se está configurando.
     *
     * @return int|null intervalo TOTP del código (para no aceptarlo de nuevo), o null si no es válido
     */
    public function pasoDelCodigo(string $secreto, string $codigo): ?int
    {
        $codigo = $this->normalizarCodigo($codigo);
        if ($codigo === null) {
            return null;
        }

        $paso = $this->google2fa->verifyKeyNewer($secreto, $codigo, 0, self::VENTANA);

        return $paso === false ? null : (int) $paso;
    }

    /** Verifica el código del usuario y lo marca como usado. */
    public function verificarCodigo(User $user, string $codigo): bool
    {
        $codigo = $this->normalizarCodigo($codigo);
        if ($codigo === null || ! $user->tieneDosFactores()) {
            return false;
        }

        $paso = $this->google2fa->verifyKeyNewer(
            $user->two_factor_secret,
            $codigo,
            (int) ($user->two_factor_ultimo_paso ?? 0),
            self::VENTANA
        );

        if ($paso === false) {
            return false;
        }

        $user->forceFill(['two_factor_ultimo_paso' => $paso])->save();

        return true;
    }

    /** Usa un código de recuperación (se elimina al usarlo). */
    public function usarCodigoRecuperacion(User $user, string $codigo): bool
    {
        $codigo = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $codigo));
        if ($codigo === '' || ! $user->tieneDosFactores()) {
            return false;
        }

        $hashes = $user->two_factor_recovery_codes ?? [];
        foreach ($hashes as $i => $hash) {
            if (Hash::check($codigo, $hash)) {
                unset($hashes[$i]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($hashes)])->save();

                return true;
            }
        }

        return false;
    }

    /**
     * Activa la verificación con el secreto ya confirmado.
     *
     * @return string[] códigos de recuperación en claro (se muestran una sola vez)
     */
    public function activar(User $user, string $secreto, int $pasoUsado): array
    {
        $codigos = $this->nuevosCodigos();

        $user->forceFill([
            'two_factor_secret' => $secreto,
            'two_factor_recovery_codes' => $this->hashes($codigos),
            'two_factor_confirmed_at' => now(),
            // El código con el que se activó ya no sirve para entrar
            'two_factor_ultimo_paso' => $pasoUsado,
        ])->save();

        return $codigos;
    }

    /** @return string[] */
    public function regenerarCodigos(User $user): array
    {
        $codigos = $this->nuevosCodigos();
        $user->forceFill(['two_factor_recovery_codes' => $this->hashes($codigos)])->save();

        return $codigos;
    }

    public function desactivar(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_ultimo_paso' => null,
        ])->save();
    }

    public function codigosRestantes(User $user): int
    {
        return count($user->two_factor_recovery_codes ?? []);
    }

    private function normalizarCodigo(string $codigo): ?string
    {
        $codigo = preg_replace('/\s+/', '', $codigo);

        return preg_match('/^\d{6}$/', $codigo) ? $codigo : null;
    }

    /** @return string[] formato "abcde-12345" */
    private function nuevosCodigos(): array
    {
        return collect(range(1, self::CANTIDAD_CODIGOS))
            ->map(fn () => strtolower(Str::random(5).'-'.Str::random(5)))
            ->all();
    }

    /** @param string[] $codigos */
    private function hashes(array $codigos): array
    {
        return array_map(fn ($c) => Hash::make(str_replace('-', '', $c)), $codigos);
    }
}
