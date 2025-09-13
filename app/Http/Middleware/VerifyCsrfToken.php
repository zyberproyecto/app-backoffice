<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * URIs que deben estar exentas de verificación CSRF.
     * Mantenemos vacío por seguridad.
     */
    protected $except = [
        // Ej.: 'webhooks/tu-servicio/*' (solo si realmente necesitás recibir POST externos sin cookie/CSRF)
    ];
}