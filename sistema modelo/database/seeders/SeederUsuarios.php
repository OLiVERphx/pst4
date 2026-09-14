<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SeederUsuarios extends Seeder
{
    public function run(): void
    {
        $superadminRoleId = DB::table('roles')->where('nombre', 'superadmin')->value('id');
        $clienteRoleId = DB::table('roles')->where('nombre', 'cliente')->value('id');
        $now = now();

        $userId = DB::table('users')->where('email', 'admin@swworld.com')->value('id');
        DB::table('users')->updateOrInsert([
            'email' => 'admin@swworld.com',
        ], [
            'rol_id' => $superadminRoleId,
            'name' => 'Superadmin',
            'apellido' => 'Super',
            'cedula' => 'V-00000000',
            'telefono' => '0414-1234567',
            'direccion' => 'Av. Libertador, Trujillo',
            'ciudad' => 'Trujillo',
            'estado' => 'Trujillo',
            'activo' => true,
            'ultimo_acceso' => $now,
            'password' => Hash::make('Admin123!'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $user = \App\Models\User::find($userId ?? DB::table('users')->where('email', 'admin@swworld.com')->value('id'));
            if ($user && method_exists($user, 'assignRole')) {
                $user->assignRole('superadmin');
            }
        }

        $clients = [
            ['name' => 'Carlos Pérez', 'apellido' => 'Pérez', 'email' => 'cliente1@swworld.com', 'cedula' => 'V-12345678', 'telefono' => '0414-2345678', 'direccion' => 'Calle 1, Valera', 'ciudad' => 'Valera', 'estado' => 'Trujillo'],
            ['name' => 'María Gómez', 'apellido' => 'Gómez', 'email' => 'cliente2@swworld.com', 'cedula' => 'V-23456789', 'telefono' => '0416-3456789', 'direccion' => 'Calle 2, Trujillo', 'ciudad' => 'Trujillo', 'estado' => 'Trujillo'],
            ['name' => 'José Fernández', 'apellido' => 'Fernández', 'email' => 'cliente3@swworld.com', 'cedula' => 'V-34567890', 'telefono' => '0426-4567890', 'direccion' => 'Calle 3, Valera', 'ciudad' => 'Valera', 'estado' => 'Trujillo'],
            ['name' => 'Ana Martínez', 'apellido' => 'Martínez', 'email' => 'cliente4@swworld.com', 'cedula' => 'V-45678901', 'telefono' => '0412-5678901', 'direccion' => 'Calle 4, Trujillo', 'ciudad' => 'Trujillo', 'estado' => 'Trujillo'],
            ['name' => 'Luis Rodríguez', 'apellido' => 'Rodríguez', 'email' => 'cliente5@swworld.com', 'cedula' => 'V-56789012', 'telefono' => '0416-6789012', 'direccion' => 'Calle 5, Valera', 'ciudad' => 'Valera', 'estado' => 'Trujillo'],
            ['name' => 'Patricia Castillo', 'apellido' => 'Castillo', 'email' => 'cliente6@swworld.com', 'cedula' => 'V-67890123', 'telefono' => '0426-7890123', 'direccion' => 'Calle 6, Trujillo', 'ciudad' => 'Trujillo', 'estado' => 'Trujillo'],
        ];

        $clientIds = [];
        foreach ($clients as $client) {
            DB::table('users')->updateOrInsert([
                'email' => $client['email'],
            ], [
                'rol_id' => $clienteRoleId,
                'name' => $client['name'],
                'apellido' => $client['apellido'],
                'cedula' => $client['cedula'],
                'telefono' => $client['telefono'],
                'direccion' => $client['direccion'],
                'ciudad' => $client['ciudad'],
                'estado' => $client['estado'],
                'activo' => true,
                'password' => Hash::make('Cliente123!'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $clientIds[] = DB::table('users')->where('email', $client['email'])->value('id');
        }

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            foreach ($clientIds as $userId) {
                $user = \App\Models\User::find($userId);
                if ($user && method_exists($user, 'assignRole')) {
                    $user->assignRole('cliente');
                }
            }
        }
    }
}
