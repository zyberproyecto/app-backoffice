<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UnidadesController extends Controller
{
    public function index(Request $r)
    {
        if (!Schema::hasTable('unidades') || !Schema::hasTable('usuario_unidad')) {
            return back()->with('error', 'Faltan tablas "unidades" o "usuario_unidad". Ejecutá migraciones en api-cooperativa.');
        }

        $buscar = trim((string) $r->query('q', ''));

        $disponibles = DB::table('unidades')
            ->when($buscar !== '', fn($q) => $q->where('codigo', 'like', "%{$buscar}%"))
            ->where('estado_unidad', 'disponible')
            ->orderBy('codigo')
            ->get();

        $asignadas = DB::table('usuario_unidad as uu')
            ->join('unidades as u', 'u.id', '=', 'uu.unidad_id')
            ->leftJoin('usuarios as us', 'us.ci_usuario', '=', 'uu.ci_usuario')
            ->where('uu.estado', 'activa')
            ->when($buscar !== '', fn($q) => $q->where('u.codigo', 'like', "%{$buscar}%"))
            ->select(
                'uu.id as asignacion_id',
                'uu.ci_usuario',
                'uu.unidad_id',
                'uu.fecha_asignacion',
                'uu.estado as estado_asignacion',
                'u.codigo',
                'u.dormitorios',
                'u.estado_unidad',
                'us.primer_nombre',
                'us.primer_apellido',
                'us.email'
            )
            ->orderBy('u.codigo')
            ->get();

        return view('unidades.index', compact('disponibles', 'asignadas', 'buscar'));
    }

    public function show(int $id)
    {
        $u = DB::table('unidades')->where('id', $id)->first();
        abort_if(!$u, 404);

        $selectAsign = [
            'uu.id as asignacion_id',
            'uu.ci_usuario',
            'uu.unidad_id',
            'uu.fecha_asignacion',
            'uu.estado',
            'uu.nota_admin',
            'us.primer_nombre',
            'us.primer_apellido',
            'us.email',
        ];

        $asignacion = DB::table('usuario_unidad as uu')
            ->leftJoin('usuarios as us', 'us.ci_usuario', '=', 'uu.ci_usuario')
            ->where('uu.unidad_id', $id)
            ->where('uu.estado', 'activa')
            ->select($selectAsign)
            ->first();

        $historial = DB::table('usuario_unidad as uu')
            ->leftJoin('usuarios as us', 'us.ci_usuario', '=', 'uu.ci_usuario')
            ->where('uu.unidad_id', $id)
            ->orderByDesc('uu.fecha_asignacion')
            ->select(
                'uu.id',
                'uu.ci_usuario',
                'uu.unidad_id',
                'uu.fecha_asignacion',
                'uu.estado',
                'uu.nota_admin',
                'us.primer_nombre',
                'us.primer_apellido'
            )
            ->get();

        return view('unidades.show', compact('u', 'asignacion', 'historial'));
    }

    public function asignar(Request $r)
    {
        $r->validate([
            'ci_usuario' => ['required', 'string', 'max:8', 'regex:/^\d{7,8}$/'],
            'unidad_id'  => ['required', 'integer', 'min:1'],
            'nota'       => ['nullable', 'string', 'max:5000'],
            'nota_admin' => ['nullable', 'string', 'max:5000'],
        ], [
            'ci_usuario.regex' => 'La CI debe tener 7 u 8 dígitos (sin puntos ni guiones).',
        ]);

        $ci       = preg_replace('/\D/', '', (string)$r->input('ci_usuario'));
        $unidadId = (int) $r->input('unidad_id');
        $nota     = trim((string) ($r->input('nota') ?? $r->input('nota_admin') ?? ''));

        $usuarioAprobado = DB::table('usuarios')
            ->where('ci_usuario', $ci)
            ->where('estado_registro', 'aprobado')
            ->exists();

        if (!$usuarioAprobado) {
            return back()->withInput()->with('error', 'El usuario no está aprobado desde Solicitudes.');
        }

        $perfilAprobado = DB::table('usuarios_perfil')
            ->where('ci_usuario', $ci)
            ->where('estado_revision', 'aprobado')
            ->exists();

        if (!$perfilAprobado) {
            return back()->withInput()->with('error', 'Perfil de socio no está aprobado.');
        }

        $aporteInicialOk = DB::table('comprobantes')
            ->where('ci_usuario', $ci)
            ->where('tipo', 'aporte_inicial')
            ->where('estado', 'aprobado')
            ->exists();

        if (!$aporteInicialOk) {
            return back()->withInput()->with('error', 'Aporte inicial no está aprobado.');
        }

        $ok = DB::transaction(function () use ($ci, $unidadId, $nota) {

            $unidad = DB::table('unidades')->lockForUpdate()->where('id', $unidadId)->first();
            if (!$unidad || $unidad->estado_unidad !== 'disponible') return false;

            $activa = DB::table('usuario_unidad')->lockForUpdate()
                ->where('ci_usuario', $ci)
                ->where('estado', 'activa')
                ->exists();
            if ($activa) return false;

            DB::table('usuario_unidad')->insert([
                'ci_usuario'       => $ci,
                'unidad_id'        => $unidadId,
                'fecha_asignacion' => now()->toDateString(),
                'estado'           => 'activa',
                'nota_admin'       => $nota !== '' ? $nota : null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // Marcar unidad
            DB::table('unidades')->where('id', $unidadId)->update([
                'estado_unidad' => 'asignada',
                'updated_at'    => now(),
            ]);

            return true;
        });

        return back()->with($ok ? 'ok' : 'error', $ok ? 'Unidad asignada.' : 'No se pudo asignar (verificá requisitos o asignación previa).');
    }

}