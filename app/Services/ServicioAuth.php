<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Servicio para autenticación (login/register/logout).
 */
class ServicioAuth
{
    /**
     * Login: valida credenciales, verifica activo y actualiza ultimo_acceso.
     * Retorna ['user' => User]
     * @throws Exception
     */
    public function login(array $credentials): array
    {
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;

        if (!$email || !$password) {
            throw new Exception('Invalid credentials.');
        }

        if (!Auth::attempt(['email' => $email, 'password' => $password])) {
            throw new Exception('Invalid credentials.');
        }

        $user = Auth::user();

        if (!$user->activo) {
            throw new Exception('User inactive.');
        }

        $user->ultimo_acceso = now();
        $user->save();

        return ['user' => $user];
    }

    /**
     * Register: crea usuario con rol cliente y retorna el modelo.
     */
    public function register(array $data): User
    {
        $clienteRoleId = DB::table('roles')->where('nombre', 'cliente')->value('id');

        $user = User::create([
            'rol_id' => $clienteRoleId,
            'name' => $data['name'] ?? null,
            'apellido' => $data['apellido'] ?? null,
            'email' => $data['email'] ?? null,
            'cedula' => $data['cedula'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'ciudad' => $data['ciudad'] ?? null,
            'estado' => $data['estado'] ?? 'Trujillo',
            'activo' => true,
            'password' => Hash::make($data['password']),
        ]);

        // Assign Spatie role if available
        if (method_exists($user, 'assignRole')) {
            try {
                $user->assignRole('cliente');
            } catch (\Exception $e) {
                // ignore if role not present
            }
        }

        return $user;
    }

    /**
     * Logout: revoca tokens y cierra sesión.
     */
    public function logout(User $user): void
    {
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        Auth::logout();
    }
}
