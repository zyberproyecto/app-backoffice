<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        /**
         * Cuenta registros con estado = 'pendiente' en una tabla dada.
         * Tolera nombres alternativos de columna (estado / estado_solicitud / status).
         * Evita LOWER() para no romper índices; usa fallback sólo si hace falta.
         */
        $countPendientes = function (string $table, array $cols = ['estado']): int {
            if (!Schema::hasTable($table)) return 0;

            $columns = Schema::getColumnListing($table);
            $col = collect($cols)->first(fn ($c) => in_array($c, $columns, true));
            if (!$col) return 0;

            try {
                // Comparación directa (aprovecha índice)
                return DB::table($table)->where($col, 'pendiente')->count();
            } catch (\Throwable $e) {
                // Fallback defensivo
                return DB::table($table)->whereRaw("LOWER({$col}) = ?", ['pendiente'])->count();
            }
        };

        // 1) Solicitudes de ingreso
        $pendientesSocios = $countPendientes('solicitudes', ['estado', 'estado_solicitud', 'status']);

        // 2) Comprobantes (separados por tipo)
        $pendientesAporteInicial = 0;
        $pendientesComprobantes  = 0; // mensuales
        $pendientesCompensatorios = 0;

        if (Schema::hasTable('comprobantes')) {
            $pendientesAporteInicial = DB::table('comprobantes')
                ->where('tipo', 'aporte_inicial')
                ->where('estado', 'pendiente')
                ->count();

            $pendientesComprobantes = DB::table('comprobantes')
                ->where('tipo', 'aporte_mensual')
                ->where('estado', 'pendiente')
                ->count();

            // NUEVO: compensatorios pendientes
            $pendientesCompensatorios = DB::table('comprobantes')
                ->where('tipo', 'compensatorio')
                ->where('estado', 'pendiente')
                ->count();
        }

        // 3) Horas de trabajo (prefiere horas_trabajo; si no, horas)
        // En tu flujo/enum el “pendiente” real es 'reportado'
        $pendientesHoras = 0;
        if (Schema::hasTable('horas_trabajo')) {
            $pendientesHoras = DB::table('horas_trabajo')->where('estado', 'reportado')->count();
        } elseif (Schema::hasTable('horas')) {
            $pendientesHoras = DB::table('horas')->where('estado', 'reportado')->count();
        }

        // 4) Exoneraciones (enum: pendiente/aprobada/rechazada)
        $pendientesExoneraciones = $countPendientes('exoneraciones', ['estado']);

        return view('dashboard', compact(
            'pendientesSocios',
            'pendientesAporteInicial',
            'pendientesComprobantes',
            'pendientesCompensatorios', // <- agregar este en la vista si querés mostrarlo
            'pendientesHoras',
            'pendientesExoneraciones'
        ));
    }
}
