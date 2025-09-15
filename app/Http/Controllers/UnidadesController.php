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
            return back()->with('error', 'Faltan tablas "unidades" o "usuario_unidad". Ejecutá las migraciones en api-cooperativa.');
        }

        $buscar = trim((string) $r->query('q', ''));

        // Disponibles
        $disponibles = DB::table('unidades')
            ->when($buscar !== '', fn($q) => $q->where('codigo', 'like', "%{$buscar}%"))
            ->where('estado_unidad', 'disponible')
            ->orderBy('codigo')
            ->get();

        // Asignadas activas (+ filtro por búsqueda)
        $asignadas = DB::table('usuario_unidad as uu')
            ->join('unidades as u', 'u.id', '=', 'uu.unidad_id')
            ->leftJoin('usuarios as us', 'us.ci_usuario', '=', 'uu.ci_usuario')
            ->where('uu.estado', 'activa')
            ->when($buscar !== '', fn($q) => $q->where('u.codigo', 'like', "%{$buscar}%"))
            ->select(
                'uu.id',
                'uu.ci_usuario',
                'uu.unidad_id',
                'uu.fecha_asignacion',
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

    // GET /admin/unidades/{id}
    public function show(int $id)
    {
        $u = DB::table('unidades')->where('id', $id)->first();
        abort_if(!$u, 404);

        $asignacion = DB::table('usuario_unidad as uu')
            ->leftJoin('usuarios as us', 'us.ci_usuario', '=', 'uu.ci_usuario')
            ->where('uu.unidad_id', $id)
            ->where('uu.estado', 'activa')
            ->select('uu.*', 'us.primer_nombre', 'us.primer_apellido', 'us.email')
            ->first();

        $historial = DB::table('usuario_unidad as uu')
            ->leftJoin('usuarios as us', 'us.ci_usuario', '=', 'uu.ci_usuario')
            ->where('uu.unidad_id', $id)
            ->orderByDesc('uu.fecha_asignacion')
            ->select('uu.*', 'us.primer_nombre', 'us.primer_apellido')
            ->get();

        return view('unidades.show', compact('u', 'asignacion', 'historial'));
    }

    // POST /admin/unidades/asignar
    public function asignar(Request $r)
    {
        $r->validate([
            'ci_usuario' => ['required', 'string', 'max:8', 'regex:/^\d{7,8}$/'],
            'unidad_id'  => ['required', 'integer'],
            'nota_admin' => ['nullable', 'string', 'max:5000'],
        ], [
            'ci_usuario.regex' => 'La CI debe tener 7 u 8 dígitos (sin puntos ni guiones).',
        ]);

        $ci       = preg_replace('/\D/', '', $r->input('ci_usuario'));
        $unidadId = (int) $r->input('unidad_id');
        $nota     = trim((string) $r->input('nota_admin'));

        $usuario = DB::table('usuarios')->where('ci_usuario', $ci)->first();
        if (!$usuario) return back()->withInput()->with('error', 'CI no encontrado en usuarios.');

        $ok = DB::transaction(function () use ($ci, $unidadId, $nota) {
            // Lock unidad
            $unidad = DB::table('unidades')->lockForUpdate()->where('id', $unidadId)->first();
            if (!$unidad) return false;
            if ($unidad->estado_unidad !== 'disponible') return false;

            // Asegurar que usuario no tenga activa
            $activa = DB::table('usuario_unidad')->lockForUpdate()
                ->where('ci_usuario', $ci)
                ->where('estado', 'activa')
                ->first();
            if ($activa) return false;

            // Insert relación
            DB::table('usuario_unidad')->insert([
                'ci_usuario'       => $ci,
                'unidad_id'        => $unidadId,
                'fecha_asignacion' => now()->toDateString(),
                'estado'           => 'activa',
                'nota_admin'       => $nota !== '' ? $nota : null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // Update estado de unidad
            DB::table('unidades')->where('id', $unidadId)->update([
                'estado_unidad' => 'asignada',
                'updated_at'    => now(),
            ]);

            return true;
        });

        return back()->with($ok ? 'ok' : 'error', $ok ? 'Unidad asignada.' : 'No se pudo asignar (verificá disponibilidad o asignación previa).');
    }

    // PUT /admin/unidades/{id}/liberar
    public function liberar($id)
    {
        $ok = DB::transaction(function () use ($id) {
            // Lock relación activa
            $rel = DB::table('usuario_unidad')->lockForUpdate()
                ->where('unidad_id', $id)
                ->where('estado', 'activa')
                ->first();

            if (!$rel) return false;

            // Lock unidad
            $unidad = DB::table('unidades')->lockForUpdate()->where('id', $id)->first();
            if (!$unidad) return false;

            // Cerrar relación
            $updateRel = [
                'estado'     => 'liberada',
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('usuario_unidad', 'fecha_liberacion')) {
                $updateRel['fecha_liberacion'] = now()->toDateString();
            }
            DB::table('usuario_unidad')->where('id', $rel->id)->update($updateRel);

            // Volver unidad a disponible
            DB::table('unidades')->where('id', $id)->update([
                'estado_unidad' => 'disponible',
                'updated_at'    => now(),
            ]);

            return true;
        });

        return back()->with($ok ? 'ok' : 'error', $ok ? 'Unidad liberada.' : 'No se pudo liberar (no hay asignación activa).');
    }
}