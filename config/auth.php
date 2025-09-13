<?php

return [

    'defaults' => [
        'guard' => 'admin',   // el panel usa este guard por defecto
        'passwords' => 'admins',
    ],

    'guards' => [
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
        // si más adelante agregás otros guards (web/api), los podés sumar acá
    ],

    'providers' => [
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
        // otros providers...
    ],

    'passwords' => [
        'admins' => [
            'provider' => 'admins',
            // En Laravel 11, la tabla por defecto es 'password_reset_tokens'
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];