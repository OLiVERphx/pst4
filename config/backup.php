<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

/*
|--------------------------------------------------------------------------
| Respaldos programados (spatie/laravel-backup v10)
|--------------------------------------------------------------------------
|
| "php artisan backup:run" se ejecuta a diario a las 02:00 (routes/console.php).
| Estructura de la v10 del paquete: la anterior era de otra versión y varias
| claves se ignoraban (los comprobantes no entraban al respaldo y faltaba
| 'notifiable', lo que hacía fallar el comando). Retención provisional:
| confirmar la política definitiva con el cliente.
|
*/

return [

    'backup' => [
        'name' => env('APP_NAME', 'smartphoneworld'),

        'source' => [
            'files' => [
                // Solo los comprobantes de pago: el código está en git y la base va en el dump
                'include' => [
                    storage_path('app/private/receipts'),
                ],
                'exclude' => [],
                'follow_links' => false,
                'ignore_unreadable_directories' => true,
                'relative_path' => storage_path('app/private'),
            ],

            'databases' => [
                env('DB_CONNECTION', 'mysql'),
            ],
        ],

        'database_dump_compressor' => null,
        'database_dump_file_timestamp_format' => null,
        'database_dump_filename_base' => 'database',
        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 9,
            'filename_prefix' => 'respaldo_',
            // Disco 'backups' (config/filesystems.php): local por defecto, s3 opcional
            'disks' => [
                'backups',
            ],
            'continue_on_failure' => false,
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        // ZIP cifrado con AES-256. Guarda esta contraseña fuera del servidor: sin ella
        // no se pueden abrir los respaldos. El panel (ServicioRespaldos) usa la misma.
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'aes256',

        'verify_backup' => true,
        'tries' => 1,
        'retry_delay' => 0,
    ],

    // Solo se avisa por correo cuando algo falla (requiere MAIL_* configurado en el .env)
    'notifications' => [
        'notifications' => [
            BackupHasFailedNotification::class => ['mail'],
            UnhealthyBackupWasFoundNotification::class => ['mail'],
            CleanupHasFailedNotification::class => ['mail'],
            BackupWasSuccessfulNotification::class => [],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => env('BACKUP_NOTIFY_MAIL', env('MAIL_FROM_ADDRESS', 'backup@example.com')),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'backup@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Smartphone World'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ],

        'webhook' => [
            'url' => '',
        ],
    ],

    'log_channel' => null,

    // Alerta si el último respaldo tiene más de un día
    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'smartphoneworld'),
            'disks' => ['backups'],
            'health_checks' => [
                MaximumAgeInDays::class => 1,
                MaximumStorageInMegabytes::class => 50000,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 7,
            'keep_weekly_backups_for_weeks' => 4,
            'keep_monthly_backups_for_months' => 6,
            'keep_yearly_backups_for_years' => 1,
            'delete_oldest_backups_when_using_more_megabytes_than' => 50000,
        ],

        'tries' => 1,
        'retry_delay' => 0,
    ],

];
