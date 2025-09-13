<?php

return [

    // Disco por defecto (privado). Para BO alcanza "local".
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        // PRIVADO: para archivos internos del BO (no públicos).
        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app/private'),
            // Mejor desactivar el servido directo salvo que lo pidas explícito
            'serve'  => env('FILESYSTEM_LOCAL_SERVE', false),
            'throw'  => false,
            'report' => false,
        ],

        // PÚBLICO: estáticos y cualquier asset que quieras exponer.
        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => rtrim(env('APP_URL'), '/').'/storage',
            'visibility' => 'public',
            'throw'      => false,
            'report'     => false,
        ],

        // OPCIONAL: si más adelante servís comprobantes desde este BO
        // (hoy estás mostrando links externos via COOP_API_FILES_BASE).
        'comprobantes_public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public/comprobantes'),
            'url'        => rtrim(env('APP_URL'), '/').'/comprobantes',
            'visibility' => 'public',
            'throw'      => false,
            'report'     => false,
        ],

        // Cloud (sin uso hoy, lo dejamos por si acaso)
        's3' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_DEFAULT_REGION'),
            'bucket'                  => env('AWS_BUCKET'),
            'url'                     => env('AWS_URL'),
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw'                   => false,
            'report'                  => false,
        ],
    ],

    // Symlinks a crear con `php artisan storage:link`
    'links' => [
        public_path('storage')      => storage_path('app/public'),
        // Symlink específico para comprobantes públicos (opcional)
        public_path('comprobantes') => storage_path('app/public/comprobantes'),
    ],
];