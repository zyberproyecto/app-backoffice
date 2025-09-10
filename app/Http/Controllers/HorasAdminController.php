<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HorasAdminController extends Controller
{
    /**
     * GET /admin/horas?estado=pendiente|aprobado|rechazado|todos&ci=XXXX
     * Muestra listado para backoffice (HTML).
     */
    public function index(Request $r)
    {
        $estado = Str::lower($r->query('estado', 'pendiente')); // UI
        $ci     = trim((string) $r->query('ci', ''));

        // Armamos query base sobre la tabla real
        $q = DB::table('horas_trabajo')
            ->select([
                'id',
                'ci_usuario',     // tu migración usa ci_usuario en horas_trabajo
                'semana',
                'fecha',
                'horas',
                'actividad',
                'descripcion',
                'estado',
                'created_at',
            ]);

        if ($estado !== 'todos') {
            $q->where('estado', $estado); // enum real: pendiente|aprobado|rechazado
        }

        if ($ci !== '') {
            $q->where('ci_usuario', $ci);
        }

        $items = $q->orderByDesc('id')->get();

        // Resumen para chips (cuentas por estado)
        $resumen = [
            'pendientes' => DB::table('horas_trabajo')->where('estado','pendiente')->count(),
            'aprobadas'  => DB::table('horas_trabajo')->where('estado','aprobado')->count(),
            'rechazadas' => DB::table('horas_trabajo')->where('estado','rechazado')->count(),
        ];

        return view('horas.index', [
            'items'   => $items,
            'resumen' => $resumen,
            'estado'  => $estado,
            'ci'      => $ci,
        ]);
    }

    /**
     * PUT /admin/horas/{id}/validar
     */
    public function validar(Request $r, int $id)
    {
        $aff = DB::table('horas_trabajo')->where('id', $id)->update([
            'estado'     => 'aprobado',
            'updated_at' => now(),
        ]);

        if ($r->wantsJson()) {
            return response()->json(['ok' => (bool)$aff], $aff ? 200 : 404);
        }

        return back()->with($aff ? 'success' : 'error', $aff ? 'Horas aprobadas.' : 'Registro no encontrado.');
    }

    /**
     * PUT /admin/horas/{id}/rechazar
     * Body opcional: motivo (para rechazar exoneración asociada también)
     */
    public function rechazar(Request $r, int $id)
    {
        $motivo = (string) ($r->input('motivo') ?? '');

        $hora = DB::table('horas_trabajo')->where('id', $id)->first();
        if (!$hora) {
            return $r->wantsJson()
                ? response()->json(['ok' => false, 'message' => 'Registro no encontrado'], 404)
                : back()->with('error','Registro no encontrado');
        }

        DB::table('horas_trabajo')->where('id', $id)->update([
            'estado'     => 'rechazado',
            'updated_at' => now(),
        ]);

        // Si hay exoneración del mismo periodo, rechazarla también (si existe)
        if (!empty($hora->semana)) {
            DB::table('exoneraciones')
                ->where('ci_usuario', $hora->ci_usuario)
                ->where('periodo', $hora->semana)
                ->update([
                    'estado'     => 'rechazado',
                    'updated_at' => now(),
                ]);
        }

        if ($r->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success','Horas rechazadas.');
    }
}