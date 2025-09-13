<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class ExoneracionAdminController extends Controller
{
    /**
     * GET /admin/exoneraciones?estado=pendiente|aprobado|rechazado|todos&ci=XXXX&per_page=25&page=1
     * Muestra el listado para el backoffice (HTML) o JSON si se solicita.
     */
    public function index(Request $r)
    {
        // Estado UI normalizado
        $estado = Str::lower($r->query('estado', 'pendiente'));
        $valid = ['pendiente','aprobado','rechazado','todos'];
        if (!in_array($estado, $valid, true)) {
            $estado = 'pendiente';
        }

        // CI normalizado (remueve puntos/guiones/espacios)
        $ciRaw = trim((string) $r->query('ci', ''));
        $ci    = $ciRaw === '' ? '' : preg_replace('/[.\-\s]/', '', $ciRaw);

        // Paginación (saneada)
        $perPage = (int) $r->query('per_page', 25);
        if ($perPage < 10) $perPage = 10;
        if ($perPage > 100) $perPage = 100;

        // Select base
        $select = [
            'id',
            'ci_usuario',
            'periodo',      // semana o etiqueta equivalente
            'motivo',
            'estado',       // pendiente|aprobado|rechazado
            'created_at',
        ];

        // Si existen columnas de notas/motivo_rechazo, las incorporamos (opcional)
        if (Schema::hasColumn('exoneraciones', 'nota_admin') || Schema::hasColumn('exoneraciones', 'motivo_rechazo')) {
            $select[] = DB::raw('COALESCE(nota_admin, motivo_rechazo, "") as nota_admin');
        }

        $q = DB::table('exoneraciones')->select($select);

        if ($estado !== 'todos') {
            $q->where('estado', $estado);
        }
        if ($ci !== '') {
            $q->where('ci_usuario', $ci);
        }

        $items = $q->orderByDesc('created_at')
                   ->orderByDesc('id')
                   ->simplePaginate($perPage)
                   ->appends($r->only(['estado','ci','per_page']));

        // Resumen (totales por estado)
        $resumen = [
            'pendientes' => DB::table('exoneraciones')->where('estado','pendiente')->count(),
            'aprobadas'  => DB::table('exoneraciones')->where('estado','aprobado')->count(),
            'rechazadas' => DB::table('exoneraciones')->where('estado','rechazado')->count(),
        ];

        // Soporte JSON opcional
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

        // Render de vista (panel)
        return view('exoneraciones.index', [
            'items'   => $items,
            'resumen' => $resumen,
            'estado'  => $estado,
            'ci'      => $ciRaw,
        ]);
    }

    /**
     * PUT /admin/exoneraciones/{id}/validar
     * Solo desde estado 'pendiente'.
     */
    public function validar(Request $r, int $id)
    {
        $result = DB::transaction(function () use ($id) {
            $row = DB::table('exoneraciones')->lockForUpdate()->where('id', $id)->first();
            if (!$row) {
                return ['status' => 404, 'ok' => false, 'msg' => 'Registro no encontrado.'];
            }
            if (strtolower($row->estado) !== 'pendiente') {
                return ['status' => 409, 'ok' => false, 'msg' => 'La exoneración no está pendiente.'];
            }

            $aff = DB::table('exoneraciones')->where('id', $id)->update([
                'estado'     => 'aprobado',
                'updated_at' => now(),
            ]);

            return ['status' => $aff ? 200 : 500, 'ok' => (bool)$aff, 'msg' => 'Exoneración aprobada.'];
        });

        if ($r->wantsJson() || $r->query('format') === 'json') {
            return response()->json(['ok' => $result['ok'], 'message' => $result['msg']], $result['status']);
        }

        $flash = $result['ok'] ? 'success' : 'error';
        return back()->with($flash, $result['msg']);
    }

    /**
     * PUT /admin/exoneraciones/{id}/rechazar
     * Body opcional: { motivo: string }
     * Solo desde estado 'pendiente'.
     */
    public function rechazar(Request $r, int $id)
    {
        $motivo = trim((string) $r->input('motivo', ''));

        $hasNotaAdmin     = Schema::hasColumn('exoneraciones', 'nota_admin');
        $hasMotivoRechazo = Schema::hasColumn('exoneraciones', 'motivo_rechazo');

        $result = DB::transaction(function () use ($id, $motivo, $hasNotaAdmin, $hasMotivoRechazo) {
            $row = DB::table('exoneraciones')->lockForUpdate()->where('id', $id)->first();
            if (!$row) {
                return ['status' => 404, 'ok' => false, 'msg' => 'Registro no encontrado.'];
            }
            if (strtolower($row->estado) !== 'pendiente') {
                return ['status' => 409, 'ok' => false, 'msg' => 'La exoneración no está pendiente.'];
            }

            $updates = [
                'estado'     => 'rechazado',
                'updated_at' => now(),
            ];
            if ($hasNotaAdmin) {
                $updates['nota_admin'] = $motivo ?: null;
            } elseif ($hasMotivoRechazo) {
                $updates['motivo_rechazo'] = $motivo ?: null;
            }

            $aff = DB::table('exoneraciones')->where('id', $id)->update($updates);

            return ['status' => $aff ? 200 : 500, 'ok' => (bool)$aff, 'msg' => 'Exoneración rechazada.'];
        });

        if ($r->wantsJson() || $r->query('format') === 'json') {
            return response()->json(['ok' => $result['ok'], 'message' => $result['msg']], $result['status']);
        }

        $flash = $result['ok'] ? 'success' : 'error';
        return back()->with($flash, $result['msg']);
    }
}