<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global (afecta web + api)
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        // Aliases de middlewares de RUTA
        $middleware->alias([
            // Backoffice (sesión guard:admin)
            'admin.only' => \App\Http\Middleware\AdminOnly::class,
            'admin'      => \App\Http\Middleware\AdminOnly::class, // alias corto

            // Auth básicos
            'auth'     => \App\Http\Middleware\Authenticate::class,
            'guest'    => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

            // Opcional: rate limiting para APIs
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ]);

        // Grupo WEB: sesión/cookies/CSRF
        $middleware->group('web', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Grupo API (stateless). Agregá throttle si querés.
        $middleware->group('api', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // 'throttle:60,1',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();