<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\ComprobanteController;

/*
|--------------------------------------------------------------------------
| Web routes – Backoffice
|--------------------------------------------------------------------------
| En este backoffice las vistas no llaman a la BD directa: consumen las APIs
| mediante fetch/XHR con Bearer <token> (que llega vía /sso y guardás en el
| navegador). Aun así, protegemos las vistas con un middleware de puerta
| (AdminOnly) para que no se vean sin haber pasado por SSO.
*/

/** Home -> panel */
Route::get('/', fn () => redirect()->route('dashboard'));

/** SSO: la landing llega con ?token=...  (vista que lo guarda y redirige) */
Route::get('/sso', function () {
    return view('sso'); // resources/views/sso.blade.php
})->name('sso');

/** Session bootstrap desde SSO (marca sso_started y rol en la sesión) */
Route::post('/session/start', function (Request $r) {
    session([
        'sso_started' => true,
        'sso_role'    => $r->input('role', 'admin'),
    ]);
    return ['ok' => true];
})->name('session.start');

/** Dashboard (protegido) */
Route::middleware('admin.only')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /** Sección Admin */
    Route::prefix('admin')->name('admin.')->group(function () {

        // Vistas
        Route::get('/solicitudes',  [SolicitudController::class,  'index'])
            ->name('solicitudes.index');

        Route::get('/comprobantes', [ComprobanteController::class,'index'])
            ->name('comprobantes.index');

        // HORAS (vistas y acciones)
        Route::get('/horas', [\App\Http\Controllers\HorasAdminController::class, 'index'])->name('horas.index');
        Route::put('/horas/{id}/validar', [\App\Http\Controllers\HorasAdminController::class, 'validar'])->name('horas.validar');
        Route::put('/horas/{id}/rechazar', [\App\Http\Controllers\HorasAdminController::class, 'rechazar'])->name('horas.rechazar');

        // EXONERACIONES (vistas y acciones)
        Route::get('/exoneraciones',               [\App\Http\Controllers\ExoneracionAdminController::class, 'index'])->name('exoneraciones.index');
        Route::put('/exoneraciones/{id}/validar',  [\App\Http\Controllers\ExoneracionAdminController::class, 'validar'])->name('exoneraciones.validar');
        Route::put('/exoneraciones/{id}/rechazar', [\App\Http\Controllers\ExoneracionAdminController::class, 'rechazar'])->name('exoneraciones.rechazar');

        // Acciones sobre solicitudes (las blades usan @csrf y @method('PUT'))
        Route::put('/solicitudes/{id}/aprobar',  [SolicitudController::class,  'aprobar'])->name('solicitudes.aprobar');
        Route::put('/solicitudes/{id}/rechazar', [SolicitudController::class,  'rechazar'])->name('solicitudes.rechazar');
    });

    /** Logout “visual”: tu vista borra el token del navegador y redirige */
    Route::post('/logout', function () {
        return view('logout'); // resources/views/logout.blade.php
    })->name('logout');
});