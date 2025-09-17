<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ComprobanteController extends Controller
{
    public function index(Request $r)
    {
        $estado = strtolower($r->query('estado', 'pendiente'));
        $tipoUi = strtolower($r->query('tipo', 'todos'));
        $mapTipo = [
            'inicial'       => 'aporte_inicial',
            'mensual'       => 'aporte_mensual',
            'compensatorio' => 'compensatorio',
            'todos'         => 'todos',
        ];
        $tipo = $mapTipo[$tipoUi] ?? 'todos';

        $q = DB::table('comprobantes');

        if ($estado !== 'todos') {
        }
        if ($tipo !== 'todos') {
            $q->where('tipo', $tipo);
        }
        if ($ci = $r->query('ci')) {
            $ci = preg_replace('/\D/', '', (string) $ci);
            if ($ci !== '') {
                $q->where('ci_usuario', $ci);
            }
        }

        $items = $q->orderByDesc('id')->paginate(20);

        return view('comprobantes.index', compact('items', 'estado', 'tipoUi'));
    }

    public function show($id)
    {
        $row = DB::table('comprobantes')->where('id', $id)->first();
        abort_if(!$row, 404);
        return view('comprobantes.show', compact('row'));
    }

    public function aprobar($id)
    {
        $adminId = Auth::guard('admin')->id();

        $ok = DB::transaction(function () use ($id, $adminId) {
            $row = DB::table('comprobantes')->lockForUpdate()->where('id', $id)->first();
            if (!$row) return false;

            if (strtolower($row->estado) === 'aprobado') return true;

            $data = [
                'estado'     => 'aprobado',
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('comprobantes', 'aprobado_por')) $data['aprobado_por'] = $adminId;
            if (Schema::hasColumn('comprobantes', 'aprobado_at'))  $data['aprobado_at'] = now();

            DB::table('comprobantes')->where('id', $id)->update($data);
            return true;
        });

        return back()->with($ok ? 'ok' : 'error', $ok ? 'Comprobante aprobado.' : 'No se pudo aprobar.');
    }

    public function rechazar(Request $r, $id)
    {
        $adminId = Auth::guard('admin')->id();

        $ok = DB::transaction(function () use ($id, $adminId, $r) {
            $row = DB::table('comprobantes')->lockForUpdate()->where('id', $id)->first();
            if (!$row) return false;

            if (strtolower($row->estado) === 'rechazado') return true;

            $data = [
                'estado'     => 'rechazado',
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('comprobantes', 'aprobado_por')) $data['aprobado_por'] = $adminId;
            if (Schema::hasColumn('comprobantes', 'aprobado_at'))  $data['aprobado_at'] = now();
            if (Schema::hasColumn('comprobantes', 'nota_admin') && $r->filled('nota')) {
                $data['nota_admin'] = $r->input('nota');
            }

            DB::table('comprobantes')->where('id', $id)->update($data);
            return true;
        });

        return back()->with($ok ? 'ok' : 'error', $ok ? 'Comprobante rechazado.' : 'No se pudo rechazar.');
    }
}