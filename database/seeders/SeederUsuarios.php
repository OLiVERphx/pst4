<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

/**
 * Seeder para usuarios: crea superadmin y clientes de prueba.
 */
class SeederUsuarios extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $superadminRole = DB::table('roles')->where('nombre', 'superadmin')->value('id') ?? 1;
        $clienteRole = DB::table('roles')->where('nombre', 'cliente')->value('id') ?? 3;

        // Usuario superadmin (firstOrCreate para evitar duplicados)
        $superadminUser = User::firstOrCreate(
            ['email' => 'admin@swworld.com'],
            [
                'name' => 'Superadmin',
                'apellido' => 'Administrador',
                'email' => 'admin@swworld.com',
                'rol_id' => $superadminRole,
                'password' => Hash::make('Admin123!'),
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        if (method_exists($superadminUser, 'assignRole')) {
            try {
                $superadminUser->assignRole('superadmin');
            } catch (\Exception $e) {
                // ignorar si no está disponible
            }
        }

        // Clientes de prueba (inserción masiva con insertOrIgnore)
        $clients = [
            ['name' => 'Carlos', 'apellido' => 'Gonzalez', 'email' => 'carlos.gonzalez@example.com', 'cedula' => 'V-10123456', 'telefono' => '0414-1234567', 'ciudad' => 'Valera'],
            ['name' => 'María', 'apellido' => 'Rodríguez', 'email' => 'maria.rodriguez@example.com', 'cedula' => 'V-20123456', 'telefono' => '0416-2345678', 'ciudad' => 'Trujillo'],
            ['name' => 'José', 'apellido' => 'Martinez', 'email' => 'jose.martinez@example.com', 'cedula' => 'V-30123456', 'telefono' => '0424-3456789', 'ciudad' => 'Valera'],
            ['name' => 'Ana', 'apellido' => 'Lopez', 'email' => 'ana.lopez@example.com', 'cedula' => 'V-40123456', 'telefono' => '0412-4567890', 'ciudad' => 'Trujillo'],
            ['name' => 'Luis', 'apellido' => 'Herrera', 'email' => 'luis.herrera@example.com', 'cedula' => 'V-50123456', 'telefono' => '0412-5678901', 'ciudad' => 'Valera'],
            ['name' => 'Sofia', 'apellido' => 'Fernandez', 'email' => 'sofia.fernandez@example.com', 'cedula' => 'V-60123456', 'telefono' => '0414-6789012', 'ciudad' => 'Trujillo'],
        ];

        $insert = [];
        foreach ($clients as $c) {
            $insert[] = [
                'rol_id' => $clienteRole,
                'name' => $c['name'],
                'apellido' => $c['apellido'],
                'email' => $c['email'],
                'cedula' => $c['cedula'],
                'telefono' => $c['telefono'],
                'direccion' => null,
                'ciudad' => $c['ciudad'],
                'estado' => 'Trujillo',
                'activo' => true,
                'password' => Hash::make('Client123!'),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('users')->insertOrIgnore($insert);
    }
}
