<?php

return [

    // Usaremos Sanctum para tokens. El guard "web" puede quedar por compatibilidad.
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'usuarios',
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'usuarios',
        ],
        // No definimos un guard "api" propio.
        // Sanctum registra su guard para tokens (auth:sanctum).
    ],

    'providers' => [
        'usuarios' => [
            'driver' => 'eloquent',
            // Mejor usar FQCN como string para evitar problemas de autoload en config:
            'model'  => App\Models\Usuario::class,
        ],
    ],

    'passwords' => [
        'usuarios' => [
            'provider' => 'usuarios',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];