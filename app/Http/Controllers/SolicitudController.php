<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class SolicitudController extends Controller
{
    /**
     * GET /api/solicitudes/pendientes  (JSON)
     * GET /admin/solicitudes           (HTML)
     * Permite ?estado=pendiente|aprobada|rechazada|todos (default: pendiente)
     */
    public function index(Request $request)
    {
        $estado = Str::lower($request->query('estado', 'pendiente'));

        // Campos: compatibilidad con nombres distintos
        $select = [
            'id',
            DB::raw('COALESCE(ci_usuario, ci) as ci_usuario'),
            DB::raw('COALESCE(nombre, nombre_completo) as nombre'),
            'email',
            'telefono',
            'menores_a_cargo',
            'dormitorios',
            'comentarios',
            'estado',
            'created_at',
        ];

        $base = DB::table('solicitudes')->select($select);

        // En la BD el enum está en minúsculas; filtramos con LOWER(...)
        if ($estado !== 'todos') {
            $base->whereRaw('LOWER(estado) = ?', [$estado]);
        }

        $items = $base->orderByDesc('created_at')->get();

        // Resumen por estado (todo en lowercase para evitar casing issues)
        $resumen = [
            'pendientes' => DB::table('solicitudes')->whereRaw('LOWER(estado) = ?', ['pendiente'])->count(),
            'aprobadas'  => DB::table('solicitudes')->whereRaw('LOWER(estado) = ?', ['aprobada'])->count(),
            'rechazadas' => DB::table('solicitudes')->whereRaw('LOWER(estado) = ?', ['rechazada'])->count(),
        ];

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'ok'      => true,
                'estado'  => $estado,
                'resumen' => $resumen,
                'items'   => $items,
            ]);
        }

        // Render de vista (panel)
        return view('solicitudes.index', compact('items', 'resumen', 'estado'));
    }

    /**
     * PUT /api/solicitudes/{id}/aprobar
     * PUT /admin/solicitudes/{id}/aprobar
     *
     * Mantiene tu comportamiento anterior (estadoSolicitud='aprobada', estadoUsuario='aprobado'),
     * y ADEMÁS crea usuarios.ci_usuario si no existe (password temporal = CI, hasheada).
     */
    public function aprobar(Request $request, $id)
    {
        return $this->aprobarYCriarUsuarioSiFalta($request, (int)$id, 'aprobada', 'aprobado');
    }

    /**
     * PUT /api/solicitudes/{id}/rechazar
     * PUT /admin/solicitudes/{id}/rechazar
     */
    public function rechazar(Request $request, $id)
    {
        // usuarios.estado_registro debe ser 'rechazado' (minúsculas, por el enum)
        return $this->cambiarEstadoSolicitudYUsuario($request, (int)$id, 'rechazada', 'rechazado');
    }

    // ----------------- Helpers -----------------

    /**
     * Aprueba la solicitud, y si no existe el usuario lo crea automáticamente.
     */
    private function aprobarYCriarUsuarioSiFalta(Request $request, int $id, string $estadoSolicitud, ?string $estadoUsuario)
    {
        return DB::transaction(function () use ($request, $id, $estadoSolicitud, $estadoUsuario) {
            $sol = DB::table('solicitudes')->where('id', $id)->first();
            if (!$sol) {
                return $this->respuesta($request, false, 'Solicitud no encontrada.', 404);
            }

            $estadoActual = Str::lower($sol->estado ?? 'pendiente');
            if ($estadoActual !== 'pendiente') {
                return $this->respuesta($request, false, 'Solo se pueden cambiar solicitudes pendientes.', 409);
            }

            // 1) Cambiar estado de la solicitud
            DB::table('solicitudes')->where('id', $id)->update([
                'estado'     => $estadoSolicitud, // 'aprobada'
                'updated_at' => now(),
            ]);

            // 2) Actualizar/crear usuario si corresponde
            $ci = $this->extraerCiUsuario($sol);
            if ($estadoUsuario !== null && $ci) {
                $existe = DB::table('usuarios')->where('ci_usuario', $ci)->first();

                if ($existe) {
                    DB::table('usuarios')
                        ->where('ci_usuario', $ci)
                        ->update([
                            'estado_registro' => $estadoUsuario, // 'aprobado'
                            'rol'             => DB::raw("COALESCE(rol, 'socio')"),
                            'updated_at'      => now(),
                        ]);
                } else {
                    // Construimos el usuario a partir de la solicitud
                    $nombreCompleto = $this->prefer($sol, ['nombre', 'nombre_completo']);
                    [$pn, $sn, $pa, $sa] = $this->splitNombre($nombreCompleto);

                    $email    = $this->prefer($sol, ['email']);
                    $telefono = $this->prefer($sol, ['telefono']);

                    // Desambiguar email único si ya existe
                    if ($email && DB::table('usuarios')->where('email', $email)->exists()) {
                        $email = $this->emailDesambiguado($email, $ci);
                    }

                    DB::table('usuarios')->insert([
                        'ci_usuario'      => $ci,
                        'primer_nombre'   => $pn,
                        'segundo_nombre'  => $sn,
                        'primer_apellido' => $pa,
                        'segundo_apellido'=> $sa,
                        'email'           => $email,
                        'telefono'        => $telefono,
                        'password'        => Hash::make($ci),  // contraseña temporal = CI
                        'estado_registro' => $estadoUsuario,    // 'aprobado'
                        'rol'             => 'socio',
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }

            return $this->respuesta($request, true, "Solicitud #{$id} aprobada y usuario habilitado para login.");
        });
    }

    /**
     * Versión “sólo cambio de estado” (la que ya tenías).
     * La dejo por si querés llamarla en otros flujos sin crear usuario.
     */
    private function cambiarEstadoSolicitudYUsuario(Request $request, int $id, string $estadoSolicitud, ?string $estadoUsuario)
    {
        if (!in_array($estadoSolicitud, ['aprobada', 'rechazada'], true)) {
            return $this->respuesta($request, false, 'Estado destino inválido.');
        }

        return DB::transaction(function () use ($request, $id, $estadoSolicitud, $estadoUsuario) {
            $solicitud = DB::table('solicitudes')->where('id', $id)->first();
            if (!$solicitud) {
                return $this->respuesta($request, false, 'Solicitud no encontrada.', 404);
            }

            $estadoActual = Str::lower($solicitud->estado ?? 'pendiente');
            if ($estadoActual !== 'pendiente') {
                return $this->respuesta($request, false, 'Solo se pueden cambiar solicitudes pendientes.', 409);
            }

            DB::table('solicitudes')->where('id', $id)->update([
                'estado'     => $estadoSolicitud,
                'updated_at' => now(),
            ]);

            if ($estadoUsuario !== null) {
                $ci = $this->extraerCiUsuario($solicitud);
                if ($ci) {
                    DB::table('usuarios')
                        ->where('ci_usuario', $ci)
                        ->update([
                            'estado_registro' => $estadoUsuario, // 'aprobado' | 'rechazado'
                            'updated_at'      => now(),
                        ]);
                }
            }

            return $this->respuesta($request, true, "Solicitud #{$id} marcada como {$estadoSolicitud}.");
        });
    }

    private function extraerCiUsuario(object $solicitud): ?string
    {
        if (isset($solicitud->ci_usuario) && $solicitud->ci_usuario) {
            return (string) $solicitud->ci_usuario;
        }
        if (isset($solicitud->ci) && $solicitud->ci) {
            return (string) $solicitud->ci;
        }
        return null;
    }

    private function prefer(object $o, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (isset($o->{$k}) && trim((string)$o->{$k}) !== '') {
                return trim((string)$o->{$k});
            }
        }
        return null;
    }

    private function splitNombre(?string $nombreCompleto): array
    {
        $nombreCompleto = trim((string)$nombreCompleto);
        if ($nombreCompleto === '') {
            return [null, null, null, null];
        }
        $parts = preg_split('/\s+/', $nombreCompleto) ?: [];

        $pNombre   = $parts[0] ?? null;
        $sNombre   = $parts[1] ?? null;

        $pApellido = null;
        $sApellido = null;
        if (count($parts) >= 3) {
            $pApellido = $parts[count($parts) - 2] ?? null;
            $sApellido = $parts[count($parts) - 1] ?? null;
        }

        return [$pNombre, $sNombre, $pApellido, $sApellido];
    }

    private function emailDesambiguado(string $email, string $ci): string
    {
        if (!str_contains($email, '@')) {
            return "{$email}.{$ci}@example.local";
        }
        [$user, $dom] = explode('@', $email, 2);
        if ($user === '') $user = 'user';
        return "{$user}+{$ci}@{$dom}";
    }

    private function respuesta(Request $request, bool $ok, string $msg, int $status = 200)
    {
        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json(['ok' => $ok, 'message' => $msg], $status);
        }

        $query = [];
        if ($f = $request->query('estado')) $query['estado'] = $f;

        return redirect()->route('admin.solicitudes.index', $query)
            ->with($ok ? 'success' : 'error', $msg);
    }
}