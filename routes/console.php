<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Tareas programadas. Desde Laravel 11 se definen aquí (app/Console/Kernel.php
| ya no se carga). En el servidor debe existir un cron que ejecute cada minuto:
|   * * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
| En local se puede usar: php artisan schedule:work
*/

// Respaldo diario de base de datos y archivos (config/backup.php).
Schedule::command('backup:run')->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->emailOutputOnFailure(env('BACKUP_NOTIFY_MAIL', env('ADMIN_EMAIL')));

// Reconstrucción del índice TF-IDF para la búsqueda inteligente.
Schedule::command('tfidf:build')->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->emailOutputOnFailure(env('BACKUP_NOTIFY_MAIL', env('ADMIN_EMAIL')));

// Liberar stock de reservas vencidas.
Schedule::command('reservations:release-expired')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// Tasa oficial BCV: el BCV publica en horario laboral; se consulta cada hora.
Schedule::command('tasa:actualizar')
    ->hourly()
    ->between('7:00', '20:00')
    ->withoutOverlapping()
    ->onOneServer();

// Reporte semanal por correo (aviso "Reporte semanal" en Mi cuenta / Configuración).
Schedule::command('reportes:semanal')
    ->weeklyOn(1, '7:45')
    ->withoutOverlapping()
    ->onOneServer();
