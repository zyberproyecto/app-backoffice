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

        /**
         * Middleware GLOBAL (corre en web + api)
         * Definimos el stack completo para asegurar orden consistente.
         * Tip: si no tenés TrustHosts/TrustProxies/TrimStrings custom, dejalos comentados.
         */
        $middleware->use([
            // \App\Http\Middleware\TrustHosts::class,
            // \App\Http\Middleware\TrustProxies::class,

            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,

            // \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        /**
         * Aliases de MIDDLEWARE DE RUTA
         * (los usás en routes: ->middleware('admin'), ->middleware('guest:admin'), etc.)
         */
        $middleware->alias([
            // Backoffice (guard: admin)
            'admin'      => \App\Http\Middleware\AdminOnly::class,
            'admin.only' => \App\Http\Middleware\AdminOnly::class, // alias extra si querés

            // Auth básicos
            'auth'       => \App\Http\Middleware\Authenticate::class,
            'guest'      => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
            'verified'   => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

            // Rate limiting (útil en API o rutas específicas)
            'throttle'   => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ]);

        /**
         * GRUPO WEB: sesión/cookies/CSRF/bindings
         */
        $middleware->group('web', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class, // opcional si usás "remember me" robusto
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        /**
         * GRUPO API (stateless). Agregá throttle si querés.
         */
        $middleware->group('api', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // 'throttle:60,1',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();