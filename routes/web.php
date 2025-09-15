<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\HorasAdminController;
use App\Http\Controllers\ExoneracionAdminController;
use App\Http\Controllers\UnidadesController;
use App\Http\Controllers\PerfilAdminController; // <-- NUEVO

/*
|--------------------------------------------------------------------------
| Utilidades
|--------------------------------------------------------------------------
*/
Route::get('/ping', fn () => [
    'pong' => true,
    'app'  => 'backoffice',
    'ts'   => now()->toIso8601String(),
]);

/*
|--------------------------------------------------------------------------
| Home → Dashboard
|--------------------------------------------------------------------------
*/
Route::redirect('/', '/dashboard');

/*
|--------------------------------------------------------------------------
| Autenticación Backoffice (guard: admin)
|--------------------------------------------------------------------------
*/
Route::middleware('guest:admin')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.post')
        ->middleware('throttle:15,1');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('admin');

/*
|--------------------------------------------------------------------------
| Zona protegida (guard: admin)
|--------------------------------------------------------------------------
*/
Route::middleware('admin')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {

        // --- Solicitudes
        Route::get('/solicitudes',               [SolicitudController::class, 'index'])->name('solicitudes.index');
        Route::get('/solicitudes/{id}',          [SolicitudController::class, 'show'])->name('solicitudes.show');
        Route::put('/solicitudes/{id}/aprobar',  [SolicitudController::class, 'aprobar'])->name('solicitudes.aprobar');
        Route::put('/solicitudes/{id}/rechazar', [SolicitudController::class, 'rechazar'])->name('solicitudes.rechazar');

        // --- Comprobantes
        Route::get('/comprobantes',               [ComprobanteController::class, 'index'])->name('comprobantes.index');
        Route::get('/comprobantes/{id}',          [ComprobanteController::class, 'show'])->name('comprobantes.show');
        Route::put('/comprobantes/{id}/aprobar',  [ComprobanteController::class, 'aprobar'])->name('comprobantes.aprobar');
        Route::put('/comprobantes/{id}/rechazar', [ComprobanteController::class, 'rechazar'])->name('comprobantes.rechazar');

        // --- Horas
        Route::get('/horas',                      [HorasAdminController::class, 'index'])->name('horas.index');
        Route::get('/horas/{id}',                 [HorasAdminController::class, 'show'])->name('horas.show');
        Route::put('/horas/{id}/aprobar',         [HorasAdminController::class, 'aprobar'])->name('horas.aprobar');
        Route::put('/horas/{id}/rechazar',        [HorasAdminController::class, 'rechazar'])->name('horas.rechazar');

        // --- Exoneraciones
        Route::get('/exoneraciones',              [ExoneracionAdminController::class, 'index'])->name('exoneraciones.index');
        Route::get('/exoneraciones/{id}',         [ExoneracionAdminController::class, 'show'])->name('exoneraciones.show');
        Route::put('/exoneraciones/{id}/aprobar', [ExoneracionAdminController::class, 'aprobar'])->name('exoneraciones.aprobar');
        Route::put('/exoneraciones/{id}/rechazar',[ExoneracionAdminController::class, 'rechazar'])->name('exoneraciones.rechazar');

        // --- Perfiles (NUEVO)
        Route::get('/perfiles',                   [PerfilAdminController::class, 'index'])->name('perfiles.index');
        Route::get('/perfiles/{ci}',              [PerfilAdminController::class, 'show'])->name('perfiles.show');
        Route::put('/perfiles/{ci}/aprobar',      [PerfilAdminController::class, 'aprobar'])->name('perfiles.aprobar');
        Route::put('/perfiles/{ci}/rechazar',     [PerfilAdminController::class, 'rechazar'])->name('perfiles.rechazar');

        // --- Unidades
        Route::get('/unidades',                   [UnidadesController::class, 'index'])->name('unidades.index');
        Route::get('/unidades/{id}',              [UnidadesController::class, 'show'])->name('unidades.show');
        Route::post('/unidades/asignar',          [UnidadesController::class, 'asignar'])->name('unidades.asignar');
        Route::put('/unidades/{id}/liberar',      [UnidadesController::class, 'liberar'])->name('unidades.liberar');
    });
});

/*
|--------------------------------------------------------------------------
| Fallback (sólo si hay sesión admin)
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return redirect()->route('dashboard');
})->middleware('admin');