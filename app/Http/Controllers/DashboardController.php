<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        // Helper para contar pendientes en una tabla, tolerando nombres de columnas
        $countPendientes = function (string $table, array $cols = ['estado']) {
            if (!Schema::hasTable($table)) {
                return 0;
            }
            $columns = Schema::getColumnListing($table);

            $col = collect($cols)->first(fn($c) => in_array($c, $columns, true));
            if (!$col) {
                return 0;
            }

            return DB::table($table)
                ->whereRaw("LOWER({$col}) = ?", ['pendiente'])
                ->count();
        };

        $pendientesSocios        = $countPendientes('solicitudes', ['estado','estado_solicitud','status']);
        $pendientesComprobantes  = Schema::hasTable('comprobantes')
            ? DB::table('comprobantes')
                ->whereRaw('LOWER(estado) = ?', ['pendiente'])
                ->where('tipo', 'mensual')
                ->count()
            : 0;
        $pendientesHoras         = $countPendientes('horas_trabajo', ['estado']);
        $pendientesExoneraciones = $countPendientes('exoneraciones', ['estado']);

        return view('dashboard', compact(
            'pendientesSocios',
            'pendientesComprobantes',
            'pendientesHoras',
            'pendientesExoneraciones'
        ));
    }
}