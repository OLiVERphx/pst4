<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\MiddlewareDosFactores;
use App\Services\ServicioAuditoria;
use App\Services\ServicioDosFactores;
use Illuminate\Http\Request;

/**
 * Pantallas de la verificación en dos pasos: pedir el código al entrar, activarla,
 * ver los códigos de recuperación y desactivarla. Ver MiddlewareDosFactores.
 */
class DosFactoresController extends Controller
{
    private const SECRETO_PENDIENTE = 'dos_factores.secreto_pendiente';
    private const CODIGOS_NUEVOS = 'dos_factores.codigos_nuevos';

    public function __construct(private ServicioDosFactores $dosFactores)
    {
    }

    // -- Al entrar -------------------------------------------------------

    public function desafio(Request $request)
    {
        if (! $request->user()->tieneDosFactores() || $this->verificado($request)) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.dos-factores');
    }

    public function verificar(Request $request)
    {
        $user = $request->user();
        if (! $user->tieneDosFactores()) {
            return redirect()->route('admin.dashboard');
        }

        $request->validate(['codigo' => 'required|string|max:32']);
        $codigo = $request->input('codigo');
        $esRecuperacion = $request->boolean('recuperacion');

        $valido = $esRecuperacion
            ? $this->dosFactores->usarCodigoRecuperacion($user, $codigo)
            : $this->dosFactores->verificarCodigo($user, $codigo);

        if (! $valido) {
            ServicioAuditoria::registrar('2fa.fallido', $user, null, ['tipo' => $esRecuperacion ? 'recuperacion' : 'app'], $user->id);

            return back()
                ->withErrors(['codigo' => $esRecuperacion ? 'Código de recuperación no válido o ya usado.' : 'Código incorrecto o vencido. Usa el que muestra la app ahora.'])
                ->with('recuperacion', $esRecuperacion);
        }

        $request->session()->regenerate();
        $request->session()->put(MiddlewareDosFactores::SESION, $user->id);

        ServicioAuditoria::registrar('2fa.verificado', $user, null, [
            'tipo' => $esRecuperacion ? 'recuperacion' : 'app',
            'codigos_restantes' => $this->dosFactores->codigosRestantes($user),
        ], $user->id);

        if ($esRecuperacion) {
            $restantes = $this->dosFactores->codigosRestantes($user);
            session()->flash('warning', "Entraste con un código de recuperación. Te quedan {$restantes}. Si perdiste el teléfono, ve a Mi cuenta y configura la verificación de nuevo.");
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    // -- Activación ------------------------------------------------------

    public function configurar(Request $request)
    {
        $user = $request->user();
        if ($user->tieneDosFactores()) {
            return redirect()->route('admin.perfil');
        }

        // El secreto se guarda en la sesión hasta que se confirma con un código:
        // recargar la página no invalida el QR ya escaneado.
        $secreto = $request->session()->get(self::SECRETO_PENDIENTE);
        if (! $secreto) {
            $secreto = $this->dosFactores->generarSecreto();
            $request->session()->put(self::SECRETO_PENDIENTE, $secreto);
        }

        return view('admin.perfil.dos-factores-configurar', [
            'qr' => $this->dosFactores->qrSvg($this->dosFactores->urlOtpauth($user, $secreto)),
            'clave' => trim(chunk_split($secreto, 4, ' ')),
            'obligatorio' => $user->requiereDosFactores(),
        ]);
    }

    public function activar(Request $request)
    {
        $user = $request->user();
        if ($user->tieneDosFactores()) {
            return redirect()->route('admin.perfil');
        }

        $request->validate(['codigo' => 'required|string|max:16']);
        $secreto = $request->session()->get(self::SECRETO_PENDIENTE);
        $paso = $secreto ? $this->dosFactores->pasoDelCodigo($secreto, $request->input('codigo')) : null;

        if ($paso === null) {
            return back()->withErrors(['codigo' => 'El código no coincide. Revisa que escaneaste este QR y que la hora del teléfono sea automática.']);
        }

        $codigos = $this->dosFactores->activar($user, $secreto, $paso);
        $request->session()->forget(self::SECRETO_PENDIENTE);
        $request->session()->put(MiddlewareDosFactores::SESION, $user->id);

        // Quien tenga la contraseña y una sesión abierta en otro equipo debe pasar por el código
        $user->cerrarOtrasSesiones($request->session()->getId());

        ServicioAuditoria::registrar('2fa.activado', $user, null, null, $user->id);

        return redirect()->route('admin.2fa.codigos')->with(self::CODIGOS_NUEVOS, $codigos);
    }

    // -- Códigos de recuperación y desactivación ----------------------------

    public function codigos(Request $request)
    {
        $codigos = $request->session()->get(self::CODIGOS_NUEVOS);
        if (! $codigos) {
            return redirect()->route('admin.perfil');
        }

        return view('admin.perfil.dos-factores-codigos', ['codigos' => $codigos]);
    }

    public function regenerar(Request $request)
    {
        $this->exigirPassword($request);
        $user = $request->user();
        abort_unless($user->tieneDosFactores(), 404);

        $codigos = $this->dosFactores->regenerarCodigos($user);
        ServicioAuditoria::registrar('2fa.codigos_regenerados', $user, null, null, $user->id);

        return redirect()->route('admin.2fa.codigos')->with(self::CODIGOS_NUEVOS, $codigos);
    }

    public function desactivar(Request $request)
    {
        $this->exigirPassword($request);
        $user = $request->user();
        abort_unless($user->tieneDosFactores(), 404);

        $this->dosFactores->desactivar($user);
        $request->session()->forget(MiddlewareDosFactores::SESION);
        ServicioAuditoria::registrar('2fa.desactivado', $user, null, null, $user->id);

        // Si su rol la exige, el middleware lo lleva a configurarla de nuevo (cambio de teléfono)
        return $user->requiereDosFactores()
            ? redirect()->route('admin.2fa.configurar')->with('success', 'Verificación anterior eliminada. Escanea el nuevo código con tu teléfono.')
            : redirect()->route('admin.perfil')->with('success', 'Verificación en dos pasos desactivada.');
    }

    private function exigirPassword(Request $request): void
    {
        $request->validateWithBag('dosFactores', [
            'password_actual' => ['required', 'current_password'],
        ], [
            'password_actual.current_password' => 'La contraseña no es correcta.',
        ], [
            'password_actual' => 'contraseña',
        ]);
    }

    private function verificado(Request $request): bool
    {
        return $request->session()->get(MiddlewareDosFactores::SESION) === $request->user()->id;
    }
}
