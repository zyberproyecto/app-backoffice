<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS
    |--------------------------------------------------------------------------
    | Para el backoffice no necesitamos CORS (mismo origen).
    | Si más adelante querés consumir /api/* desde otro dominio, agregá ese
    | patrón en 'paths' y el/los orígenes en 'allowed_origins'.
    */

    // Sin rutas -> CORS deshabilitado (mismo origen)
    'paths' => [
        // 'api/*', // descomentá si vas a exponer APIs cross-origin
    ],

    'allowed_methods' => ['*'],

    // Orígenes permitidos (vacío = ninguno)
    'allowed_origins' => [
        // 'http://127.0.0.1:5500',
        // 'http://localhost:5500',
    ],

    'allowed_origins_patterns' => [],

    // Headers permitidos (si habilitás CORS, dejá '*')
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    // Cacheo del preflight
    'max_age' => 3600,

    // Cookies/credenciales en CORS (para backoffice no hace falta)
    'supports_credentials' => false,
];