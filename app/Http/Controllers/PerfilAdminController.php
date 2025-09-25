<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PerfilAdminController extends Controller
{
    public function index(Request $r)
    {
        if (!Schema::hasTable('usuarios_perfil')) {
            abort(500, 'Falta la tabla usuarios_perfil (ejecutar migraciones en api-usuarios).');
        }

        // Normalizamos el filtro de estado
        $estado = strtolower((string)$r->query('estado', 'pendiente'));
        $valid  = ['pendiente','aprobado','rechazado','todas'];
        if (!in_array($estado, $valid, true)) {
            $estado = 'pendiente';
        }

        $q = DB::table('usuarios_perfil as p');

        if ($estado !== 'todas') {
            if ($estado === 'pendiente') {
                // Incluimos NULL / '' como "pendiente" para no perder registros
                $q->where(function ($w) {
                    $w->whereRaw('LOWER(COALESCE(p.estado_revision, "")) = ?', ['pendiente'])
                      ->orWhereNull('p.estado_revision')
                      ->orWhere('p.estado_revision', '');
                });
            } else {
                $q->whereRaw('LOWER(p.estado_revision) = ?', [$estado]);
            }
        }

        if ($ci = $r->query('ci')) {
            $ci = preg_replace('/\D/', '', (string) $ci);
            if ($ci !== '') {
                // Permitimos búsqueda parcial por CI
                $q->where('p.ci_usuario', 'like', "%{$ci}%");
            }
        }

        // Orden: primero pendientes, luego aprobados, luego rechazados; después por fecha
        $items = $q->orderByRaw("FIELD(LOWER(COALESCE(p.estado_revision,'pendiente')), 'pendiente','aprobado','rechazado')")
                   ->orderByDesc('p.updated_at')
                   ->paginate(20);

        return view('perfiles.index', compact('items', 'estado'));
    }

    public function show(string $ci)
    {
        if (!Schema::hasTable('usuarios_perfil')) {
            abort(500, 'Falta la tabla usuarios_perfil.');
        }

        $ci = preg_replace('/\D/', '', $ci);

        $perfil = DB::table('usuarios_perfil')->where('ci_usuario', $ci)->first();
        if (!$perfil) abort(404);

        $usuario = Schema::hasTable('usuarios')
            ? DB::table('usuarios')->where('ci_usuario', $ci)->first()
            : null;

        return view('perfiles.show', compact('perfil', 'usuario'));
    }

    public function aprobar(string $ci)
    {
        // Guard explícito de admin (con fallback)
        $adminId = auth('admin')->id() ?? auth()->id();
        $ci = preg_replace('/\D/', '', $ci);

        $ok = DB::transaction(function () use ($ci, $adminId) {
            $row = DB::table('usuarios_perfil')->lockForUpdate()->where('ci_usuario', $ci)->first();
            if (!$row) return false;

            if (strtolower((string)$row->estado_revision) === 'aprobado') return true;

            DB::table('usuarios_perfil')->where('ci_usuario', $ci)->update([
                'estado_revision' => 'aprobado',
                'aprobado_por'    => $adminId,
                'aprobado_at'     => now(),
                'updated_at'      => now(),
            ]);

            return true;
        });

        return back()->with($ok ? 'ok' : 'error', $ok ? 'Perfil aprobado.' : 'No se pudo aprobar (no existe).');
    }

    public function rechazar(Request $r, string $ci)
    {
        // Guard explícito de admin (con fallback)
        $adminId = auth('admin')->id() ?? auth()->id();
        $ci = preg_replace('/\D/', '', $ci);

        $ok = DB::transaction(function () use ($ci, $adminId) {
            $row = DB::table('usuarios_perfil')->lockForUpdate()->where('ci_usuario', $ci)->first();
            if (!$row) return false;

            if (strtolower((string)$row->estado_revision) === 'rechazado') return true;

            DB::table('usuarios_perfil')->where('ci_usuario', $ci)->update([
                'estado_revision' => 'rechazado',
                'aprobado_por'    => $adminId,
                'aprobado_at'     => now(),
                'updated_at'      => now(),
            ]);

            return true;
        });

        return back()->with($ok ? 'ok' : 'error', $ok ? 'Perfil rechazado.' : 'No se pudo rechazar (no existe).');
    }
}