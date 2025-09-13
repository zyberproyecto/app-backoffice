<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComprobanteController extends Controller
{
    /**
     * Vista de comprobantes con filtro por tipo (inicial|mensual) y estado.
     * Estado: pendiente | aprobado | rechazado | todos
     * En DB: tipo 'aporte_inicial' | 'mensual'; estados en minúsculas.
     * Soporta paginado con ?pp=20 (rango 5..100).
     */
    public function index(Request $r)
    {
        $tipoParam = trim((string) $r->query('tipo'));               // 'inicial' | 'mensual' | null
        $estadoUi  = strtolower($r->query('estado', 'pendiente'));   // para la UI

        // Tamaño de página (pp): entre 5 y 100, default 20
        $perPage = max(5, min((int) $r->query('pp', 20), 100));

        // Mapear tipo de UI -> DB
        $tipoDb = match ($tipoParam) {
            'inicial' => 'aporte_inicial',
            'mensual' => 'mensual',
            default   => null,
        };

        // Validar/normalizar estado
        $estadoDb = match ($estadoUi) {
            'pendiente', 'aprobado', 'rechazado' => $estadoUi,
            'todos' => 'todos',
            default => 'pendiente',
        };

        $select = [
            'id',
            'ci_usuario',
            'tipo',
            'monto',
            'fecha_pago',
            'estado',
            'archivo',
            DB::raw('COALESCE(nota_admin, motivo_rechazo, "") as nota_admin'),
            'created_at',
        ];

        $q = DB::table('comprobantes')->select($select);
        if ($tipoDb) {
            $q->where('tipo', $tipoDb);
        }
        if ($estadoDb !== 'todos') {
            $q->where('estado', $estadoDb);
        }

        // 🔹 Paginado con query-string preservada (estado/tipo/pp)
        $items = $q->orderByDesc('id')->paginate($perPage)->withQueryString();

        // Resumen que respeta el filtro por tipo (si existe)
        $countBy = function (string $estado) use ($tipoDb) {
            $qb = DB::table('comprobantes')->where('estado', $estado);
            if ($tipoDb) $qb->where('tipo', $tipoDb);
            return $qb->count();
        };

        $resumen = [
            'pendientes' => $countBy('pendiente'),
            'aprobados'  => $countBy('aprobado'),
            'rechazados' => $countBy('rechazado'),
        ];

        // (Opcional) salida JSON paginada
        if ($r->wantsJson() || $r->query('format') === 'json') {
            return response()->json([
                'ok'      => true,
                'estado'  => $estadoUi,
                'tipo'    => $tipoParam,
                'resumen' => $resumen,
                'items'   => $items->items(),
                'meta'    => [
                    'total'         => $items->total(),
                    'per_page'      => $items->perPage(),
                    'current_page'  => $items->currentPage(),
                    'last_page'     => $items->lastPage(),
                    'from'          => $items->firstItem(),
                    'to'            => $items->lastItem(),
                ],
            ]);
        }

        return view('comprobantes.index', [
            'items'   => $items,
            'resumen' => $resumen,
            'tipo'    => $tipoParam, // UI friendly
            'estado'  => $estadoUi,
        ]);
    }

    /**
     * Aprobar (validar) comprobante: solo desde estado 'pendiente'.
     */
    public function validar(Request $r, int $id)
    {
        $result = DB::transaction(function () use ($id) {
            $row = DB::table('comprobantes')->lockForUpdate()->where('id', $id)->first();
            if (!$row) {
                return ['status' => 404, 'ok' => false, 'msg' => "No se encontró el comprobante #{$id}."];
            }
            if (strtolower((string) $row->estado) !== 'pendiente') {
                return ['status' => 409, 'ok' => false, 'msg' => "El comprobante #{$id} no está pendiente."];
            }

            $aff = DB::table('comprobantes')->where('id', $id)->update([
                'estado'     => 'aprobado',
                // Futuro: 'aprobado_por_ci' => auth('admin')->user()?->ci_usuario,
                'updated_at' => now(),
            ]);

            return ['status' => $aff ? 200 : 500, 'ok' => (bool) $aff, 'msg' => "Comprobante #{$id} aprobado."];
        });

        if ($r->wantsJson() || $r->query('format') === 'json') {
            return response()->json(['ok' => $result['ok'], 'message' => $result['msg']], $result['status']);
        }

        $flashType = $result['ok'] ? 'success' : 'error';
        return redirect()->route('admin.comprobantes.index')->with($flashType, $result['msg']);
    }

    /**
     * Rechazar comprobante: solo desde 'pendiente'.
     * Requiere motivo (422 si falta en JSON/HTML).
     */
    public function rechazar(Request $r, int $id)
    {
        $motivo = trim((string) $r->input('motivo', ''));
        if ($motivo === '') {
            $msg = 'Debés indicar un motivo de rechazo.';
            if ($r->wantsJson() || $r->query('format') === 'json') {
                return response()->json(['ok' => false, 'message' => $msg], 422);
            }
            return back()->withInput()->with('error', $msg);
        }

        $result = DB::transaction(function () use ($id, $motivo) {
            $row = DB::table('comprobantes')->lockForUpdate()->where('id', $id)->first();
            if (!$row) {
                return ['status' => 404, 'ok' => false, 'msg' => "No se encontró el comprobante #{$id}."];
            }
            if (strtolower((string) $row->estado) !== 'pendiente') {
                return ['status' => 409, 'ok' => false, 'msg' => "El comprobante #{$id} no está pendiente."];
            }

            $aff = DB::table('comprobantes')->where('id', $id)->update([
                'estado'         => 'rechazado',
                'motivo_rechazo' => $motivo,
                // Futuro: 'rechazado_por_ci' => auth('admin')->user()?->ci_usuario,
                'updated_at'     => now(),
            ]);

            return ['status' => $aff ? 200 : 500, 'ok' => (bool) $aff, 'msg' => "Comprobante #{$id} rechazado."];
        });

        if ($r->wantsJson() || $r->query('format') === 'json') {
            return response()->json(['ok' => $result['ok'], 'message' => $result['msg']], $result['status']);
        }

        $flashType = $result['ok'] ? 'success' : 'error';
        return redirect()->route('admin.comprobantes.index')->with($flashType, $result['msg']);
    }
}