<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\HorasAdminController;
use App\Http\Controllers\ExoneracionAdminController;

/**
 * Home → Dashboard
 * (Si no hay sesión, el middleware 'admin' redirige a /login)
 */
Route::redirect('/', '/dashboard');

// -------------------- AUTENTICACIÓN (Backoffice) --------------------
Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

    // Rate limit para evitar fuerza bruta (15 intentos / min)
    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.post')
        ->middleware('throttle:15,1');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('admin');

// -------------------- ZONA PROTEGIDA (guard: admin) --------------------
Route::middleware('admin')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Sección Admin
    Route::prefix('admin')->name('admin.')->group(function () {

        // --- Solicitudes (vistas y acciones)
        Route::get('/solicitudes', [SolicitudController::class, 'index'])
            ->name('solicitudes.index');
        Route::put('/solicitudes/{id}/aprobar',  [SolicitudController::class, 'aprobar'])
            ->name('solicitudes.aprobar');
        Route::put('/solicitudes/{id}/rechazar', [SolicitudController::class, 'rechazar'])
            ->name('solicitudes.rechazar');

        // --- Comprobantes (vistas y acciones)
        Route::get('/comprobantes', [ComprobanteController::class, 'index'])
            ->name('comprobantes.index');
        Route::put('/comprobantes/{id}/validar',  [ComprobanteController::class, 'validar'])
            ->name('comprobantes.validar');
        Route::put('/comprobantes/{id}/rechazar', [ComprobanteController::class, 'rechazar'])
            ->name('comprobantes.rechazar');

        // --- Horas (vistas y acciones)
        Route::get('/horas', [HorasAdminController::class, 'index'])
            ->name('horas.index');
        Route::put('/horas/{id}/validar',  [HorasAdminController::class, 'validar'])
            ->name('horas.validar');
        Route::put('/horas/{id}/rechazar', [HorasAdminController::class, 'rechazar'])
            ->name('horas.rechazar');

        // --- Exoneraciones (vistas y acciones)
        Route::get('/exoneraciones', [ExoneracionAdminController::class, 'index'])
            ->name('exoneraciones.index');
        Route::put('/exoneraciones/{id}/validar',  [ExoneracionAdminController::class, 'validar'])
            ->name('exoneraciones.validar');
        Route::put('/exoneraciones/{id}/rechazar', [ExoneracionAdminController::class, 'rechazar'])
            ->name('exoneraciones.rechazar');
    });
});

// Fallback (solo si hay sesión admin): redirige a dashboard
Route::fallback(function () {
    return redirect()->route('dashboard');
})->middleware('admin');