<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Quien podría llamar al /api del backoffice (no es el caso actual)
    'allowed_origins' => [
        'http://127.0.0.1:5500',
        'http://localhost:5500',
        'http://127.0.0.1:8003',
        'http://localhost:8003',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,
];