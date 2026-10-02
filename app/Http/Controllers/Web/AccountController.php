<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ServicioAuditoria;
use App\Services\ServicioPedidos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    /**
     * Muestra la vista principal de la cuenta del cliente:
     * datos de perfil y su historial de pedidos.
     */
    public function index(ServicioPedidos $service)
    {
        $usuario = auth()->user();

        $pedidos = $usuario
            ->orders()
            ->with(['items.product', 'payment'])
            ->orderByDesc('created_at')
            ->paginate(10);

        $estados = $service->obtenerFlujoEstados();

        return view('web.mi-cuenta', compact('usuario', 'pedidos', 'estados'));
    }

    /**
     * Actualiza los datos del perfil y dirección del cliente.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'apellido'  => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email,' . $user->id,
            'cedula'    => 'nullable|string|max:20|unique:users,cedula,' . $user->id,
            'telefono'  => 'nullable|string|max:20',
            'ciudad'    => 'nullable|string|max:80',
            'direccion' => 'nullable|string|max:500',
            'password'  => 'nullable|string|min:8|confirmed',
        ], [
            'name.required'      => 'El nombre es obligatorio.',
            'apellido.required'  => 'El apellido es obligatorio.',
            'email.required'     => 'El correo electrónico es obligatorio.',
            'email.email'        => 'Ingresa un correo electrónico válido.',
            'email.unique'       => 'Este correo electrónico ya está registrado por otro usuario.',
            'cedula.unique'      => 'Esta cédula ya está registrada por otro usuario.',
            'telefono.max'       => 'El teléfono no puede superar 20 caracteres.',
            'password.min'       => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $datosAntes = $user->only(['name', 'apellido', 'email', 'cedula', 'telefono', 'ciudad', 'direccion']);

        $user->name      = $validated['name'];
        $user->apellido  = $validated['apellido'];
        $user->email     = $validated['email'];
        $user->cedula    = $validated['cedula'] ?? null;
        $user->telefono  = $validated['telefono'] ?? null;
        $user->ciudad    = $validated['ciudad'] ?? null;
        $user->direccion = $validated['direccion'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $datosDespues = $user->only(['name', 'apellido', 'email', 'cedula', 'telefono', 'ciudad', 'direccion']);

        ServicioAuditoria::registrar(
            'usuario.perfil_actualizado',
            $user,
            $datosAntes,
            $datosDespues,
            $user->id
        );

        return redirect()->route('web.account')->with('success', 'Tus datos han sido actualizados correctamente.');
    }
}
