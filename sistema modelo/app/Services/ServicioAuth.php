<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServicioAuth
{
    /**
     * Login de usuario y creación de token.
     *
     * @param array<string, mixed> $credentials
     * @return array<string, mixed>
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(array $credentials): array
    {
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? $credentials['contrasena'] ?? null;

        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        if (! $user->activo) {
            throw ValidationException::withMessages([
                'email' => ['El usuario está inactivo.'],
            ]);
        }

        $user->ultimo_acceso = now();
        $user->save();

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Registro de usuario cliente.
     */
    public function register(array $data): User
    {
        $roleId = DB::table('roles')->where('nombre', 'cliente')->value('id');

        $user = User::create([
            'rol_id' => $roleId,
            'name' => $data['name'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'cedula' => $data['cedula'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'ciudad' => $data['ciudad'] ?? null,
            'estado' => $data['estado'] ?? 'Trujillo',
            'activo' => true,
            'password' => Hash::make($data['contrasena']),
        ]);

        if (method_exists($user, 'assignRole')) {
            $user->assignRole('cliente');
        }

        return $user;
    }

    /**
     * Revocar todos los tokens del usuario.
     */
    public function logout(User $user): void
    {
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }
    }
}
