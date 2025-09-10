<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComprobanteController extends Controller
{
    /**
     * Vista de comprobantes con filtro por tipo (inicial|mensual) y estado.
     * Estado admite: pendiente | aprobado | rechazado | todos
     * NOTA: en DB los estados son minúsculas y el tipo inicial se guarda como 'aporte_inicial'.
     */
    public function index(Request $r)
    {
        $tipoParam = $r->query('tipo');                       // 'inicial' | 'mensual' | null
        $estadoUi  = strtolower($r->query('estado', 'pendiente')); // para la UI

        // Mapear tipo de la UI al almacenado en DB
        $tipoDb = match ($tipoParam) {
            'inicial' => 'aporte_inicial',
            'mensual' => 'mensual',
            default   => null,
        };

        // Normalizar estado a los valores reales del enum en DB
        $estadoDb = match ($estadoUi) {
            'pendiente' => 'pendiente',
            'aprobado'  => 'aprobado',
            'rechazado' => 'rechazado',
            'todos'     => 'todos',
            default     => 'pendiente',
        };

        $select = [
            'id',
            DB::raw('COALESCE(ci_usuario, ci) as ci_usuario'),
            'tipo',
            'monto',
            'fecha_pago',
            'estado',
            'archivo',
            DB::raw('COALESCE(nota_admin, motivo_rechazo) as nota_admin'),
            'created_at',
        ];

        $q = DB::table('comprobantes')->select($select);

        if ($tipoDb) {
            $q->where('tipo', $tipoDb);
        }

        if ($estadoDb !== 'todos') {
            $q->where('estado', $estadoDb);
        }

        $items = $q->orderByDesc('id')->get();

        // Resumen (siempre con los valores reales de DB)
        $resumen = [
            'pendientes' => DB::table('comprobantes')->where('estado', 'pendiente')->count(),
            'aprobados'  => DB::table('comprobantes')->where('estado', 'aprobado')->count(),
            'rechazados' => DB::table('comprobantes')->where('estado', 'rechazado')->count(),
        ];

        return view('comprobantes.index', [
            'items'   => $items,
            'resumen' => $resumen,
            'tipo'    => $tipoParam,   // mantenemos lo que vino en la URL para la UI
            'estado'  => $estadoUi,    // idem
        ]);
    }

    /**
     * Aprobar (validar) comprobante.
     */
    public function validar(Request $r, int $id)
    {
        $aff = DB::table('comprobantes')->where('id', $id)->update([
            'estado'     => 'aprobado',   // <-- enum real en minúsculas
            'updated_at' => now(),
        ]);

        if ($r->wantsJson()) {
            return response()->json(
                ['ok' => (bool)$aff, 'message' => $aff ? "Comprobante #{$id} aprobado." : "No se encontró el comprobante."],
                $aff ? 200 : 404
            );
        }

        return redirect()->route('admin.comprobantes.index')
            ->with('success', $aff ? "Comprobante #{$id} aprobado." : "No se encontró el comprobante.");
    }

    /**
     * Rechazar comprobante.
     */
    public function rechazar(Request $r, int $id)
    {
        $motivo = $r->input('motivo');

        $aff = DB::table('comprobantes')->where('id', $id)->update([
            'estado'         => 'rechazado', // <-- enum real en minúsculas
            'motivo_rechazo' => $motivo,
            'updated_at'     => now(),
        ]);

        if ($r->wantsJson()) {
            return response()->json(
                ['ok' => (bool)$aff, 'message' => $aff ? "Comprobante #{$id} rechazado." : "No se encontró el comprobante."],
                $aff ? 200 : 404
            );
        }

        return redirect()->route('admin.comprobantes.index')
            ->with('success', $aff ? "Comprobante #{$id} rechazado." : "No se encontró el comprobante.");
    }
}