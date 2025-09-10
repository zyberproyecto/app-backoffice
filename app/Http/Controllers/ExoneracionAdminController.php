<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExoneracionAdminController extends Controller
{
    /**
     * GET /admin/exoneraciones?estado=pendiente|aprobado|rechazado|todos&ci=XXXX
     * Muestra el listado para el backoffice (HTML).
     */
    public function index(Request $r)
    {
        $estado = Str::lower($r->query('estado', 'pendiente')); // UI
        $ci     = trim((string) $r->query('ci', ''));

        $q = DB::table('exoneraciones')
            ->select([
                'id',
                'ci_usuario',
                'periodo',      // semana o etiqueta equivalente
                'motivo',
                'estado',       // pendiente|aprobado|rechazado
                'created_at',
            ]);

        if ($estado !== 'todos') {
            $q->where('estado', $estado);
        }

        if ($ci !== '') {
            $q->where('ci_usuario', $ci);
        }

        $items = $q->orderByDesc('id')->get();

        $resumen = [
            'pendientes' => DB::table('exoneraciones')->where('estado','pendiente')->count(),
            'aprobadas'  => DB::table('exoneraciones')->where('estado','aprobado')->count(),
            'rechazadas' => DB::table('exoneraciones')->where('estado','rechazado')->count(),
        ];

        return view('exoneraciones.index', [
            'items'   => $items,
            'resumen' => $resumen,
            'estado'  => $estado,
            'ci'      => $ci,
        ]);
    }

    /**
     * PUT /admin/exoneraciones/{id}/validar
     */
    public function validar(Request $r, int $id)
    {
        $aff = DB::table('exoneraciones')->where('id', $id)->update([
            'estado'     => 'aprobado',
            'updated_at' => now(),
        ]);

        if ($r->wantsJson()) {
            return response()->json(['ok' => (bool)$aff], $aff ? 200 : 404);
        }

        return back()->with($aff ? 'success' : 'error', $aff ? 'Exoneración aprobada.' : 'Registro no encontrado.');
    }

    /**
     * PUT /admin/exoneraciones/{id}/rechazar
     * Body opcional: { motivo: string }
     */
    public function rechazar(Request $r, int $id)
    {
        $motivo = (string) ($r->input('motivo') ?? '');

        $aff = DB::table('exoneraciones')->where('id', $id)->update([
            'estado'     => 'rechazado',
            'updated_at' => now(),
        ]);

        if ($r->wantsJson()) {
            return response()->json(['ok' => (bool)$aff], $aff ? 200 : 404);
        }

        return back()->with($aff ? 'success' : 'error', $aff ? 'Exoneración rechazada.' : 'Registro no encontrado.');
    }
}