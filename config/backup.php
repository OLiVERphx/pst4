<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de backups
    |--------------------------------------------------------------------------
    |
    | Archivo de configuración para spatie/laravel-backup. Comentarios y
    | valores provisionales en ESPAÑOL según la política de trabajo del
    | proyecto. NO fijar la política definitiva hasta que el cliente la
    | confirme (preguntar cuál política prefiere).
    |
    */

    'backup' => [
        'name' => env('APP_NAME', 'smartphoneworld'),

        // Destinos donde se guardarán los backups. Usar un disco distinto al
        // disco de producción. En este proyecto se usará el disco 'backups'
        // definido en config/filesystems.php (apunta a S3 u otro almacenamiento
        // remoto). Asegurarse que las credenciales serán configuradas en .env.
        'destination' => [
            'disks' => [
                'backups',
            ],
        ],

        // Si se activa, el backup intentará comprimir y agrupar archivos.
        'temporary_directory' => storage_path('app/backup-temp'),
    ],

    'database_dump_compressor' => null,

    'source' => [
        // Base de datos a incluir: spatie se encargará de volcar TODAS las
        // bases de datos configuradas en la conexión por defecto. Recalcar
        // regla del proyecto: ninguna validación de precio/stock depende del
        // cliente — las restauraciones y verificaciones deben usarse solo en
        // ambiente controlado.
        'databases' => [
            'mysql',
        ],

        'files' => [
            // Incluir carpeta de comprobantes de pago que está bajo storage/app/private/receipts
            'include' => [
                storage_path('app/private/receipts'),
            ],

            'exclude' => [],

            'followLinks' => false,
        ],
    ],

    'cleanup' => [
        // Políticas de retención provisionales (EN ESPAÑOL). Preguntar al
        // cliente qué política prefiere antes de fijar valores definitivos.
        // Ejemplo provisional:
        // - conservar diarios por 7 días
        // - conservar semanales por 4 semanas
        // - conservar mensuales por 6 meses
        // Estos valores son solo sugeridos y están marcados para revisión.
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7, // Sugería: 7 (PREGUNTAR)
            'keep_daily_backups_for_days' => 7, // Sugería: 7 (PREGUNTAR)
            'keep_weekly_backups_for_weeks' => 4, // Sugería: 4 (PREGUNTAR)
            'keep_monthly_backups_for_months' => 6, // Sugería: 6 (PREGUNTAR)
            'keep_yearly_backups_for_years' => 1,
            'delete_oldest_backups_when_using_more_megabytes_than' => 50000,
        ],
    ],

    'notifications' => [
        // Notificar en caso de fallo. Aquí se configuran canales y clases
        // handlers. Asegurarse de tener MAIL y SLACK configurados en .env.
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailed::class => ['mail', 'slack'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFound::class => ['mail', 'slack'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailed::class => ['mail', 'slack'],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFound::class => [],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessful::class => [],
        ],

        // Configuración de las notificaciones por correo
        // Usar MAIL_FROM_ADDRESS como fallback y, si no existe, un email
        // seguro por defecto para evitar errores durante package:discover.
        'mail' => [
            'to' => env('BACKUP_NOTIFY_MAIL', env('MAIL_FROM_ADDRESS', 'backup@localhost')),
        ],

        // Configuración de Slack: enviar notificaciones a un canal concreto
        // Se incluyen claves explícitas (incluso si quedan null) para evitar
        // errores durante package:discover cuando la configuración esté ausente.
        'slack' => [
            'webhook_url' => env('BACKUP_SLACK_WEBHOOK_URL', ''),
            'channel' => env('BACKUP_SLACK_CHANNEL'),
            'username' => 'smartphoneworld-backup',
            'icon' => env('BACKUP_SLACK_ICON', null),
        ],
    ],

    'monitor_backups' => [
        // Monitor para verificar que al menos exista un backup diario
        [
            'name' => env('APP_NAME', 'smartphoneworld'),
            'disks' => ['backups'],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 50000,
            ],
        ],
    ],

    'temp_dir' => storage_path('app/backup-temp'),
];
