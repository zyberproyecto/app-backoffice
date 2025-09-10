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
        // CORS global
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        // 👇 Alias de middlewares de ruta (agrego admin.only y dejo admin por compatibilidad)
        $middleware->alias([
            'admin.only' => \App\Http\Middleware\AdminOnly::class,
            'admin'      => \App\Http\Middleware\AdminOnly::class, // opcional (compat)
            'auth'       => \App\Http\Middleware\Authenticate::class,
            'verified'   => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);

        // Grupo WEB: sesión/cookies/CSRF
        $middleware->group('web', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class, // opcional
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Grupo API (liviano)
        $middleware->group('api', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();