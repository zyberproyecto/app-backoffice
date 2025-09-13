<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * A dónde redirigir cuando no está autenticado en rutas web.
     * Para requests API que esperan JSON, devolvemos 401 sin redirigir.
     */
    protected function redirectTo($request): ?string
    {
        if ($request->expectsJson()) {
            return null; // deja que el framework responda 401 JSON
        }

        // Login del Backoffice
        return route('login');
    }
}