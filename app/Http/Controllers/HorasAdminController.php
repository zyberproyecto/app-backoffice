<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HorasAdminController extends Controller
{
    public function index(Request $r)
    {
        $estado = strtolower($r->query('estado', 'reportado'));
        $q = DB::table('horas_trabajo');

        if ($estado !== 'todas') {
            $q->where('estado', $estado);
        }

        if ($ci = $r->query('ci')) {
            $ci = preg_replace('/\D/', '', (string) $ci);
            if ($ci !== '') {
                $q->where('ci_usuario', $ci);
            }
        }

        $items = $q->orderByDesc('id')->paginate(20)->appends($r->query());

        return view('horas.index', compact('items', 'estado'));
    }

    public function show($id)
    {
        $row = DB::table('horas_trabajo')->where('id', $id)->first();
        abort_if(!$row, 404);

        $exoneracion = DB::table('exoneraciones')
            ->where('ci_usuario', $row->ci_usuario)
            ->whereDate('semana_inicio', $row->semana_inicio)
            ->first();

        return view('horas.show', compact('row', 'exoneracion'));
    }

    public function aprobar($id)
    {
        $adminId = Auth::guard('admin')->id();

        $ok = DB::transaction(function () use ($id, $adminId) {
            $row = DB::table('horas_trabajo')->lockForUpdate()->where('id', $id)->first();
            if (!$row) return false;
            if (strtolower($row->estado) === 'aprobado') return true;

            $data = ['estado' => 'aprobado', 'updated_at' => now()];
            if (Schema::hasColumn('horas_trabajo', 'aprobado_por')) $data['aprobado_por'] = $adminId;
            if (Schema::hasColumn('horas_trabajo', 'aprobado_at'))  $data['aprobado_at'] = now();

            DB::table('horas_trabajo')->where('id', $id)->update($data);
            return true;
        });

        return back()->with($ok ? 'ok' : 'error', $ok ? 'Horas aprobadas.' : 'No se pudo aprobar.');
    }

    public function rechazar($id)
    {
        $adminId = Auth::guard('admin')->id();

        $ok = DB::transaction(function () use ($id, $adminId) {
            $row = DB::table('horas_trabajo')->lockForUpdate()->where('id', $id)->first();
            if (!$row) return false;
            if (strtolower($row->estado) === 'rechazado') return true;

            $data = ['estado' => 'rechazado', 'updated_at' => now()];
            if (Schema::hasColumn('horas_trabajo', 'aprobado_por')) $data['aprobado_por'] = $adminId;
            if (Schema::hasColumn('horas_trabajo', 'aprobado_at'))  $data['aprobado_at'] = now();

            DB::table('horas_trabajo')->where('id', $id)->update($data);

            DB::table('exoneraciones')
                ->where('ci_usuario', $row->ci_usuario)
                ->whereDate('semana_inicio', $row->semana_inicio)
                ->where('estado', 'pendiente')
                ->update([
                    'estado'     => 'rechazada',
                    'updated_at' => now(),
                ]);

            return true;
        });

        return back()->with($ok ? 'ok' : 'error', $ok ? 'Horas rechazadas y exoneración (si existía) marcada como rechazada.' : 'No se pudo rechazar.');
    }
}