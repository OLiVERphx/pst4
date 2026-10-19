<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ServicioAuditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/**
 * Cuenta del usuario del panel: contraseña y avisos que quiere recibir.
 * Cualquier usuario del panel puede usarlo (no requiere configuracion.editar).
 */
class PerfilController extends Controller
{
    public function show()
    {
        return view('admin.perfil.index');
    }

    public function actualizarPassword(Request $request)
    {
        // Bolsa de errores propia: el formulario comparte página con otros en Configuración
        $request->validateWithBag('password', [
            'password_actual' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:password_actual', Password::min(8)->letters()->numbers()],
        ], [
            'password_actual.current_password' => 'La contraseña actual no es correcta.',
            'password.different' => 'La nueva contraseña debe ser distinta de la actual.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ], [
            'password_actual' => 'contraseña actual',
            'password' => 'nueva contraseña',
        ]);

        $user = $request->user();
        $user->password = $request->input('password');
        $user->save();

        // Cierra las demás sesiones abiertas con la contraseña anterior
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }
        $request->session()->regenerate();

        ServicioAuditoria::registrar('usuario.password_cambiada', $user);

        return back()->with('success', 'Contraseña actualizada. Se cerraron tus otras sesiones abiertas.');
    }

    public function actualizarNotificaciones(Request $request)
    {
        $user = $request->user();
        $disponibles = array_keys($user->notificacionesDisponibles());

        $request->validate([
            'notificaciones' => 'array',
            'notificaciones.*' => ['string', 'in:' . implode(',', array_keys(User::NOTIFICACIONES))],
        ]);
        $activas = $request->input('notificaciones', []);

        $antes = $user->preferencias_notificacion;
        $user->preferencias_notificacion = collect($disponibles)
            ->mapWithKeys(fn ($tipo) => [$tipo => in_array($tipo, $activas, true)])
            ->all();
        $user->save();

        ServicioAuditoria::registrar('usuario.notificaciones_actualizadas', $user,
            ['preferencias_notificacion' => $antes], ['preferencias_notificacion' => $user->preferencias_notificacion]);

        return back()->with('success', 'Preferencias de notificación guardadas.');
    }
}
