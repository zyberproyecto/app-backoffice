<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\HorasAdminController;
use App\Http\Controllers\ExoneracionAdminController;

Route::get('/', fn () => redirect()->route('dashboard'));

/** SSO: la landing llega con ?token=... (vista que lo guarda y redirige) */
Route::get('/sso', function () {
    return view('sso'); // resources/views/sso.blade.php
})->name('sso');

/** Bootstrap de sesión desde SSO (marca sso_started y rol en la sesión) */
Route::post('/session/start', function (Request $r) {
    session([
        'sso_started' => true,
        'sso_role'    => $r->input('role', 'admin'),
    ]);
    return ['ok' => true];
})->name('session.start');

/** Zona protegida */
Route::middleware('admin.only')->group(function () {

    /** Dashboard */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /** Sección Admin */
    Route::prefix('admin')->name('admin.')->group(function () {

        // --- Solicitudes (vistas y acciones)
        Route::get('/solicitudes', [SolicitudController::class, 'index'])
            ->name('solicitudes.index');
        Route::put('/solicitudes/{id}/aprobar',  [SolicitudController::class, 'aprobar'])
            ->name('solicitudes.aprobar');
        Route::put('/solicitudes/{id}/rechazar', [SolicitudController::class, 'rechazar'])
            ->name('solicitudes.rechazar');

        // --- Comprobantes (vista y acciones)
        Route::get('/comprobantes', [ComprobanteController::class, 'index'])
            ->name('comprobantes.index');
        Route::put('/comprobantes/{id}/validar',  [ComprobanteController::class, 'validar'])
            ->name('comprobantes.validar');
        Route::put('/comprobantes/{id}/rechazar', [ComprobanteController::class, 'rechazar'])
            ->name('comprobantes.rechazar');

        // --- Horas (vista y acciones)
        Route::get('/horas', [HorasAdminController::class, 'index'])
            ->name('horas.index');
        Route::put('/horas/{id}/validar',  [HorasAdminController::class, 'validar'])
            ->name('horas.validar');
        Route::put('/horas/{id}/rechazar', [HorasAdminController::class, 'rechazar'])
            ->name('horas.rechazar');

        // --- Exoneraciones (vista y acciones)
        Route::get('/exoneraciones', [ExoneracionAdminController::class, 'index'])
            ->name('exoneraciones.index');
        Route::put('/exoneraciones/{id}/validar',  [ExoneracionAdminController::class, 'validar'])
            ->name('exoneraciones.validar');
        Route::put('/exoneraciones/{id}/rechazar', [ExoneracionAdminController::class, 'rechazar'])
            ->name('exoneraciones.rechazar');
    });

    /** Logout “visual” (tu vista borra el token del navegador y redirige) */
    Route::post('/logout', function () {
        return view('logout'); // resources/views/logout.blade.php
    })->name('logout');
});