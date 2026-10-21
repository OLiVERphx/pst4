<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ServicioAuditoria;
use App\Services\ServicioDosFactores;
use Illuminate\Console\Command;

/**
 * Último recurso si el único superadministrador perdió el teléfono y los códigos de
 * recuperación (los demás usuarios los restablece el superadmin desde Usuarios).
 */
class RestablecerDosFactores extends Command
{
    protected $signature = 'usuarios:restablecer-2fa {email : Correo del usuario}';

    protected $description = 'Quita la verificación en dos pasos de un usuario (perdió el teléfono y los códigos)';

    public function handle(ServicioDosFactores $dosFactores): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('No existe un usuario con ese correo.');
            return self::FAILURE;
        }

        if (! $user->tieneDosFactores()) {
            $this->info('Ese usuario no tiene la verificación en dos pasos activa.');
            return self::SUCCESS;
        }

        $dosFactores->desactivar($user);
        $user->cerrarOtrasSesiones('');
        ServicioAuditoria::registrar('2fa.restablecido', $user, null, ['por' => 'consola'], null);

        $this->info("Listo. {$user->email} deberá configurarla de nuevo al entrar al panel.");

        return self::SUCCESS;
    }
}
