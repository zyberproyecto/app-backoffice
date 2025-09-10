<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Adónde redirigir cuando no hay sesión (peticiones web).
     */
    protected function redirectTo($request): ?string
    {
        // Si NO es una petición JSON, mandamos al login del backoffice
        return $request->expectsJson() ? null : route('login');
    }
}