<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\SeederCategorias;
use Database\Seeders\SeederMarcas;
use Database\Seeders\SeederRoles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Primera puesta en marcha en un servidor nuevo (Railway, VPS).
 *
 * A diferencia de db:seed NO crea clientes, productos ni pedidos de prueba, y el
 * superadministrador sale de ADMIN_EMAIL / ADMIN_PASSWORD: el seeder de desarrollo deja
 * admin@swworld.com con una contraseña conocida, que no puede existir en una URL pública.
 *
 * Es seguro ejecutarlo en cada arranque: si ya existe un superadmin no toca nada
 * (no vuelve a poner permisos ni a cambiar contraseñas).
 */
class InicializarProduccion extends Command
{
    protected $signature = 'produccion:inicializar';

    protected $description = 'Crea roles, marcas, categorías y el primer superadministrador (sin datos de prueba)';

    public function handle(): int
    {
        $rolSuperadmin = DB::table('roles')->where('nombre', 'superadmin')->value('id');
        if ($rolSuperadmin && User::where('rol_id', $rolSuperadmin)->exists()) {
            $this->info('Ya está inicializado: no se cambia nada.');
            return self::SUCCESS;
        }

        $datos = [
            'email' => config('app.admin_email'),
            'password' => config('app.admin_password'),
            'nombre' => config('app.admin_nombre'),
        ];
        $validador = Validator::make($datos, [
            'email' => 'required|email|max:255',
            'password' => ['required', 'string', Password::min(12)->letters()->numbers()],
            'nombre' => 'required|string|max:100',
        ], [], [
            'email' => 'ADMIN_EMAIL',
            'password' => 'ADMIN_PASSWORD',
            'nombre' => 'ADMIN_NOMBRE',
        ]);
        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $error) {
                $this->error($error);
            }
            $this->line('Define ADMIN_EMAIL y ADMIN_PASSWORD (mínimo 12 caracteres, con letras y números) y vuelve a desplegar.');
            return self::FAILURE;
        }

        $this->callSilent('db:seed', ['--class' => SeederRoles::class, '--force' => true]);
        $this->callSilent('db:seed', ['--class' => SeederMarcas::class, '--force' => true]);
        $this->callSilent('db:seed', ['--class' => SeederCategorias::class, '--force' => true]);

        $admin = User::create([
            'rol_id' => DB::table('roles')->where('nombre', 'superadmin')->value('id'),
            'name' => $datos['nombre'],
            'apellido' => '',
            'email' => $datos['email'],
            'password' => $datos['password'],
            'activo' => true,
        ]);
        $admin->assignRole('superadmin');

        // Sin tasa del día el checkout en bolívares no puede validar pagos; el cron la actualiza cada hora
        // en horario laboral, pero la primera hay que traerla ahora (si falla, no bloquea la instalación)
        try {
            $this->callSilent('tasa:actualizar');
        } catch (\Throwable) {
        }

        $this->info("Inicializado. Superadministrador: {$admin->email}");
        $this->line('Roles, marcas y categorías creados. Los productos se cargan desde el panel.');

        return self::SUCCESS;
    }
}
