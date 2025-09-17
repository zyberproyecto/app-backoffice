<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $countPendientes = function (string $table, array $cols = ['estado']): int {
            if (!Schema::hasTable($table)) return 0;

            $columns = Schema::getColumnListing($table);
            $col = collect($cols)->first(fn ($c) => in_array($c, $columns, true));
            if (!$col) return 0;

            try {
                return DB::table($table)->where($col, 'pendiente')->count();
            } catch (\Throwable $e) {
                return DB::table($table)->whereRaw("LOWER({$col}) = ?", ['pendiente'])->count();
            }
        };

        $pendientesSocios = $countPendientes('solicitudes', ['estado', 'estado_solicitud', 'status']);
        $pendientesAporteInicial = 0;
        $pendientesComprobantes  = 0; 
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
            $pendientesCompensatorios = DB::table('comprobantes')
                ->where('tipo', 'compensatorio')
                ->where('estado', 'pendiente')
                ->count();
        }

        $pendientesHoras = 0;
        if (Schema::hasTable('horas_trabajo')) {
            $pendientesHoras = DB::table('horas_trabajo')->where('estado', 'reportado')->count();
        } elseif (Schema::hasTable('horas')) {
            $pendientesHoras = DB::table('horas')->where('estado', 'reportado')->count();
        }

        $pendientesExoneraciones = $countPendientes('exoneraciones', ['estado']);

        return view('dashboard', compact(
            'pendientesSocios',
            'pendientesAporteInicial',
            'pendientesComprobantes',
            'pendientesCompensatorios', 
            'pendientesHoras',
            'pendientesExoneraciones'
        ));
    }
}
