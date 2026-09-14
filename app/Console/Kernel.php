<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Comandos Artisan registrados.
     *
     * @var array
     */
    protected $commands = [
        // Registrar comandos personalizados si los hay
        \App\Console\Commands\BuildTfidfIndex::class,
    ];

    /**
     * Definición del schedule de comandos programados.
     * Aquí se programa la ejecución diaria del backup y la reconstrucción del índice TF-IDF.
     *
     * Reglas del proyecto: la tarea se ejecuta en el servidor y envía
     * notificaciones en caso de fallo. Se usa withoutOverlapping para
     * evitar carreras y onOneServer para entornos con múltiples instancias.
     */
    protected function schedule(Schedule $schedule)
    {
        // Ejecuta backups diarios que incluyen base de datos completa y los archivos
        // configurados en config/backup.php. Cambiar la hora si el cliente lo solicita.
        $schedule->command('backup:run')->dailyAt('02:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->emailOutputOnFailure(env('BACKUP_NOTIFY_MAIL', env('ADMIN_EMAIL')));

        // Reconstrucción diaria del índice TF-IDF para búsquedas inteligentes.
        // Se ejecuta a las 03:00 para no competir con el backup.
        $schedule->command('tfidf:build')->dailyAt('03:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->emailOutputOnFailure(env('BACKUP_NOTIFY_MAIL', env('ADMIN_EMAIL')));

        $schedule->command('reservations:release-expired')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer();
    }

    /**
     * Registro de comandos.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
