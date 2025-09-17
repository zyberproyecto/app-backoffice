<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExoneracionAdminController extends Controller
{
    public function index(Request $r)
    {
        $estado = strtolower($r->query('estado', 'pendiente'));
        $q = DB::table('exoneraciones');

        if ($estado !== 'todas') {
            $q->where('estado', $estado);
        }

        if ($ci = $r->query('ci')) {
            $ci = preg_replace('/\D/', '', (string) $ci);
            if ($ci !== '') {
                $q->where('ci_usuario', $ci);
            }
        }

        $exoneraciones = $q->orderByDesc('id')->paginate(20)->appends($r->query());

        return view('exoneraciones.index', compact('exoneraciones', 'estado'));
    }

    public function show($id)
    {
        $exo = DB::table('exoneraciones')->where('id', $id)->first();
        abort_if(!$exo, 404);

        $hora = DB::table('horas_trabajo')
            ->where('ci_usuario', $exo->ci_usuario)
            ->whereDate('semana_inicio', $exo->semana_inicio)
            ->first();

        return view('exoneraciones.show', compact('exo', 'hora'));
    }

    public function aprobar($id)
    {
        $adminId = Auth::guard('admin')->id();

        $ok = DB::transaction(function () use ($id, $adminId) {
            $exo = DB::table('exoneraciones')->lockForUpdate()->where('id', $id)->first();
            if (!$exo) return false;

            $estado = strtolower($exo->estado);
            if ($estado === 'aprobada') return true;
            if ($estado === 'rechazada') return false;

            $data = ['estado' => 'aprobada', 'updated_at' => now()];
            if (Schema::hasColumn('exoneraciones', 'aprobado_por')) $data['aprobado_por'] = $adminId;
            if (Schema::hasColumn('exoneraciones', 'aprobado_at'))  $data['aprobado_at'] = now();

            DB::table('exoneraciones')->where('id', $id)->update($data);

            DB::table('horas_trabajo')
                ->where('ci_usuario', $exo->ci_usuario)
                ->whereDate('semana_inicio', $exo->semana_inicio)
                ->where('estado', 'reportado')
                ->update([
                    'estado'     => 'aprobado',
                    'updated_at' => now(),
                ]);

            return true;
        });

        return back()->with($ok ? 'ok' : 'error', $ok ? 'Exoneración aprobada.' : 'No se pudo aprobar (ya rechazada o inexistente).');
    }

    public function rechazar(Request $r, $id)
    {
        $adminId = Auth::guard('admin')->id();

        $ok = DB::transaction(function () use ($id, $adminId, $r) {
            $exo = DB::table('exoneraciones')->lockForUpdate()->where('id', $id)->first();
            if (!$exo) return false;

            $estado = strtolower($exo->estado);
            if ($estado === 'rechazada') return true;
            if ($estado === 'aprobada') return false;

            $data = ['estado' => 'rechazada', 'updated_at' => now()];
            if (Schema::hasColumn('exoneraciones', 'aprobado_por')) $data['aprobado_por'] = $adminId;
            if (Schema::hasColumn('exoneraciones', 'aprobado_at'))  $data['aprobado_at'] = now();

            if (Schema::hasColumn('exoneraciones', 'nota_admin')) {
                $nota = (string) $r->input('nota', '');
                $nota = trim(mb_substr($nota, 0, 500));
                if ($nota !== '') {
                    $data['nota_admin'] = $nota;
                }
            }

            DB::table('exoneraciones')->where('id', $id)->update($data);
            return true;
        });

        return back()->with($ok ? 'ok' : 'error', $ok ? 'Exoneración rechazada.' : 'No se pudo rechazar (ya aprobada o inexistente).');
    }
}