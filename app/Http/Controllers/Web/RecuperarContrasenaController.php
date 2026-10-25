<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Turnstile;
use App\Services\ServicioAuditoria;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * "¿Olvidaste tu contraseña?" para clientes y personal: enlace de un solo uso por correo, válido 60 minutos.
 * La respuesta es la misma exista o no el correo, para no revelar quién tiene cuenta.
 */
class RecuperarContrasenaController extends Controller
{
    public const MENSAJE_ENVIADO = 'Si el correo está registrado, te enviamos un enlace para crear una contraseña nueva. Revisa también la carpeta de spam. El enlace vence en 60 minutos.';

    public function formulario()
    {
        // Sin correo configurado el enlace nunca llegaría: no se ofrece el formulario
        abort_unless(config('mail.recuperacion_disponible'), 404);

        return view('web.auth.recuperar');
    }

    public function enviar(Request $request)
    {
        abort_unless(config('mail.recuperacion_disponible'), 404);

        $request->validate([
            'email' => 'required|email',
            'cf-turnstile-response' => [new Turnstile()],
        ], ['email.required' => 'Escribe tu correo electrónico.', 'email.email' => 'El correo no es válido.']);

        $estado = Password::sendResetLink($request->only('email'));
        if ($estado === Password::RESET_LINK_SENT) {
            ServicioAuditoria::registrar('password.enlace_enviado', User::where('email', $request->email)->first(), null, null, null);
        }

        return back()->with('status', self::MENSAJE_ENVIADO);
    }

    public function formularioNuevo(Request $request, string $token)
    {
        return view('web.auth.restablecer', ['token' => $token, 'email' => $request->query('email', '')]);
    }

    public function restablecer(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $estado = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            // Quien tuviera la contraseña anterior pierde las sesiones abiertas
            $user->cerrarOtrasSesiones('');
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
            event(new PasswordReset($user));
            ServicioAuditoria::registrar('password.restablecida', $user, null, null, $user->id);
        });

        if ($estado !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'El enlace no es válido o ya venció. Solicita uno nuevo.']);
        }

        return redirect()->route('web.login')->with('status', 'Tu contraseña se cambió. Ya puedes iniciar sesión.');
    }
}
