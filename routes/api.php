<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\ComprobanteController;

// Salud pública para probar que el servicio responde
Route::get('/ping', fn () => ['pong' => true]);

// Todas las APIs del backoffice requieren token + rol admin
Route::middleware(['auth:sanctum', 'abilities:admin'])->group(function () {

    // Perfil rápido (útil para debug en Insomnia)
    Route::get('/me', function () {
        $u = request()->user();
        return [
            'ci_usuario' => $u->ci_usuario ?? null,
            'rol'        => $u->rol ?? null,
            'estado'     => $u->estado_registro ?? null,
        ];
    });

    // --- Solicitudes (API) ---
    // Consejo: llamá con Accept: application/json o ?format=json para forzar JSON
    Route::get('/solicitudes',               [SolicitudController::class, 'index']);
    Route::put('/solicitudes/{id}/aprobar',  [SolicitudController::class, 'aprobar']);
    Route::put('/solicitudes/{id}/rechazar', [SolicitudController::class, 'rechazar']);

    // --- Comprobantes (API) ---
    // Unificado con las rutas web: validar|rechazar
    Route::put('/comprobantes/{id}/validar',  [ComprobanteController::class, 'validar']);
    Route::put('/comprobantes/{id}/rechazar', [ComprobanteController::class, 'rechazar']);
});