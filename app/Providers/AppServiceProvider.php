<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Aquí irían binds al contenedor si más adelante agregás servicios.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Compatibilidad con longitudes de índice en algunas colaciones.
        Schema::defaultStringLength(191);

        // Si APP_URL es https, forzamos el esquema https en URLs generadas.
        $appUrl = (string) config('app.url');
        if ($appUrl !== '' && str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }

        // Paginación con vistas Bootstrap (coincide con tus clases .table, .btn, etc).
        Paginator::useBootstrap();
    }
}