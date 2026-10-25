<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Envía un correo de prueba para comprobar la configuración (MAIL_MAILER, clave y remitente).
 */
class ProbarCorreo extends Command
{
    protected $signature = 'correo:probar {email : Dirección que recibirá la prueba}';

    protected $description = 'Envía un correo de prueba con la configuración actual';

    public function handle(): int
    {
        $mailer = config('mail.default');
        $remitente = config('mail.from.address');
        $this->line("Mailer: {$mailer} · Remitente: {$remitente}");

        if ($mailer === 'log') {
            $this->warn('MAIL_MAILER=log: el correo solo se escribe en el registro, no llega a ningún buzón.');
        }

        try {
            Mail::raw("Este es un correo de prueba de " . config('app.name') . ".\nSi lo recibiste, la recuperación de contraseña y el reporte semanal ya pueden enviarse.", function ($m) {
                $m->to($this->argument('email'))->subject('Correo de prueba - ' . config('app.name'));
            });
        } catch (\Throwable $e) {
            $this->error('No se pudo enviar: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Enviado. Revisa la bandeja de entrada (y la carpeta de spam) de ' . $this->argument('email') . '.');

        return self::SUCCESS;
    }
}
