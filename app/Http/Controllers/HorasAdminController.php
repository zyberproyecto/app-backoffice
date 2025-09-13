<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HorasAdminController extends Controller
{
    /**
     * GET /admin/horas?estado=pendiente|aprobado|rechazado|todos&ci=XXXX&per_page=25&page=1
     * Listado para backoffice (HTML) o JSON si se solicita.
     */
    public function index(Request $r)
    {
        // Normalización de estado (UI)
        $estado = Str::lower($r->query('estado', 'pendiente'));
        $valid  = ['pendiente','aprobado','rechazado','todos'];
        if (!in_array($estado, $valid, true)) {
            $estado = 'pendiente';
        }

        // CI normalizado (quita puntos/guiones/espacios)
        $ciRaw = trim((string) $r->query('ci', ''));
        $ci    = $ciRaw === '' ? '' : preg_replace('/[.\-\s]/', '', $ciRaw);

        // Paginación (saneada)
        $perPage = (int) $r->query('per_page', 25);
        if ($perPage < 10)  $perPage = 10;
        if ($perPage > 100) $perPage = 100;

        // Select fijo según el schema de horas_trabajo
        $select = [
            'id',
            'ci_usuario',
            'semana',
            'fecha',
            'horas',
            'actividad',
            'descripcion',
            'estado',
            'created_at',
        ];

        $q = DB::table('horas_trabajo')->select($select);

        if ($estado !== 'todos') {
            $q->where('estado', $estado); // enum real: pendiente|aprobado|rechazado
        }
        if ($ci !== '') {
            $q->where('ci_usuario', $ci);
        }

        $items = $q->orderByDesc('created_at')
                   ->orderByDesc('id')
                   ->simplePaginate($perPage)
                   ->appends($r->only(['estado','ci','per_page']));

        // Resumen (chips)
        $resumen = [
            'pendientes' => DB::table('horas_trabajo')->where('estado','pendiente')->count(),
            'aprobadas'  => DB::table('horas_trabajo')->where('estado','aprobado')->count(),
            'rechazadas' => DB::table('horas_trabajo')->where('estado','rechazado')->count(),
        ];

        // JSON opcional
        if ($r->wantsJson() || $r->query('format') === 'json') {
            return response()->json([
                'ok'      => true,
                'estado'  => $estado,
                'ci'      => $ciRaw,
                'resumen' => $resumen,
                'meta'    => [
                    'per_page' => $items->perPage(),
                    'current'  => $items->currentPage(),
                    'has_more' => $items->hasMorePages(),
                ],
                'items'   => $items->items(),
            ]);
        }

        return view('horas.index', [
            'items'   => $items,
            'resumen' => $resumen,
            'estado'  => $estado,
            'ci'      => $ciRaw,
        ]);
    }

    /**
     * PUT /admin/horas/{id}/validar
     * Solo desde estado 'pendiente'.
     */
    public function validar(Request $r, int $id)
    {
        $result = DB::transaction(function () use ($id) {
            $row = DB::table('horas_trabajo')->lockForUpdate()->where('id', $id)->first();
            if (!$row) {
                return ['status' => 404, 'ok' => false, 'msg' => 'Registro no encontrado.'];
            }
            if (strtolower($row->estado) !== 'pendiente') {
                return ['status' => 409, 'ok' => false, 'msg' => 'El registro no está pendiente.'];
            }

            $aff = DB::table('horas_trabajo')->where('id', $id)->update([
                'estado'     => 'aprobado',
                'updated_at' => now(),
            ]);

            return ['status' => $aff ? 200 : 500, 'ok' => (bool)$aff, 'msg' => 'Horas aprobadas.'];
        });

        if ($r->wantsJson() || $r->query('format') === 'json') {
            return response()->json(['ok' => $result['ok'], 'message' => $result['msg']], $result['status']);
        }

        $flashType = $result['ok'] ? 'success' : 'error';
        return back()->with($flashType, $result['msg']);
    }

    /**
     * PUT /admin/horas/{id}/rechazar
     * Solo desde estado 'pendiente'.
     */
    public function rechazar(Request $r, int $id)
    {
        $result = DB::transaction(function () use ($id) {
            $row = DB::table('horas_trabajo')->lockForUpdate()->where('id', $id)->first();
            if (!$row) {
                return ['status' => 404, 'ok' => false, 'msg' => 'Registro no encontrado.'];
            }
            if (strtolower($row->estado) !== 'pendiente') {
                return ['status' => 409, 'ok' => false, 'msg' => 'El registro no está pendiente.'];
            }

            $aff = DB::table('horas_trabajo')->where('id', $id)->update([
                'estado'     => 'rechazado',
                'updated_at' => now(),
            ]);

            return ['status' => $aff ? 200 : 500, 'ok' => (bool)$aff, 'msg' => 'Horas rechazadas.'];
        });

        if ($r->wantsJson() || $r->query('format') === 'json') {
            return response()->json(['ok' => $result['ok'], 'message' => $result['msg']], $result['status']);
        }

        $flashType = $result['ok'] ? 'success' : 'error';
        return back()->with($flashType, $result['msg']);
    }
}