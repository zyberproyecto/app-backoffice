<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class SolicitudController extends Controller
{
    /**
     * GET /admin/solicitudes?estado=pendiente|aprobado|rechazado|todos&per_page=25&page=1
     * HTML por defecto; JSON si ?format=json o Accept: application/json.
     */
    public function index(Request $request)
    {
        // Estado normalizado
        $estado = Str::lower($request->query('estado', 'pendiente'));
        $valid  = ['pendiente','aprobado','rechazado','todos'];
        if (!in_array($estado, $valid, true)) {
            $estado = 'pendiente';
        }

        // Paginación (saneada)
        $perPage = (int) $request->query('per_page', 25);
        if ($perPage < 10)  $perPage = 10;
        if ($perPage > 100) $perPage = 100;

        // Select tolerante a nombres de columnas desde la landing
        $select = [
            'id',
            DB::raw('COALESCE(ci_usuario, ci) as ci_usuario'),
            DB::raw('COALESCE(nombre, nombre_completo) as nombre'),
            'email',
            'telefono',
            DB::raw('COALESCE(menores_a_cargo, menores_cargo) as menores_cargo'),
            DB::raw('COALESCE(dormitorios, intereses) as dormitorios'),
            DB::raw('COALESCE(comentarios, mensaje) as comentarios'),
            'estado',
            'created_at',
        ];

        $q = DB::table('solicitudes')->select($select);

        if ($estado !== 'todos') {
            $q->whereRaw('LOWER(estado) = ?', [$estado]);
        }

        $items = $q->orderByDesc('created_at')
                   ->orderByDesc('id')
                   ->paginate($perPage)
                   ->appends($request->only(['estado','per_page']));

        // Resumen por estado
        $resumen = [
            'pendientes' => DB::table('solicitudes')->whereRaw('LOWER(estado) = ?', ['pendiente'])->count(),
            'aprobados'  => DB::table('solicitudes')->whereRaw('LOWER(estado) = ?', ['aprobado'])->count(),
            'rechazados' => DB::table('solicitudes')->whereRaw('LOWER(estado) = ?', ['rechazado'])->count(),
        ];

        // JSON opcional
        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'ok'      => true,
                'estado'  => $estado,
                'resumen' => $resumen,
                'meta'    => [
                    'per_page'   => $items->perPage(),
                    'current'    => $items->currentPage(),
                    'last_page'  => $items->lastPage(),
                    'total'      => $items->total(),
                    'has_more'   => $items->hasMorePages(),
                ],
                'items'   => $items->items(),
            ]);
        }

        return view('solicitudes.index', [
            'items'   => $items,
            'resumen' => $resumen,
            'estado'  => $estado,
        ]);
    }

    /**
     * PUT /admin/solicitudes/{id}/aprobar
     * Aprueba la solicitud y crea/actualiza usuario en BD (sin APIs).
     */
    public function aprobar(Request $request, int $id)
    {
        return $this->aprobarYCriarUsuarioSiFalta($request, $id, 'aprobado', 'aprobado');
    }

    /**
     * PUT /admin/solicitudes/{id}/rechazar
     * Rechaza la solicitud y (si existe) marca el usuario correspondiente.
     */
    public function rechazar(Request $request, int $id)
    {
        return $this->cambiarEstadoSolicitudYUsuario($request, $id, 'rechazado', 'rechazado');
    }

    // ----------------- Helpers -----------------

    private function aprobarYCriarUsuarioSiFalta(Request $request, int $id, string $estadoSolicitud, ?string $estadoUsuario)
    {
        return DB::transaction(function () use ($request, $id, $estadoSolicitud, $estadoUsuario) {
            $sol = DB::table('solicitudes')->lockForUpdate()->where('id', $id)->first();
            if (!$sol) {
                return $this->respuesta($request, false, 'Solicitud no encontrada.', 404);
            }

            $estadoActual = Str::lower($sol->estado ?? 'pendiente');
            if ($estadoActual !== 'pendiente') {
                return $this->respuesta($request, false, 'Solo se pueden cambiar solicitudes pendientes.', 409);
            }

            // 1) Cambiar estado de la solicitud (aprobado)
            DB::table('solicitudes')->where('id', $id)->update([
                'estado'     => $estadoSolicitud,
                'updated_at' => now(),
            ]);

            // 2) Crear/actualizar usuario
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
                    $nombreCompleto = $this->prefer($sol, ['nombre', 'nombre_completo']);
                    [$pn, $sn, $pa, $sa] = $this->splitNombre($nombreCompleto);

                    $email    = $this->prefer($sol, ['email']);
                    $telefono = $this->prefer($sol, ['telefono']);

                    // Desambiguar email si ya existe
                    if ($email && DB::table('usuarios')->where('email', $email)->exists()) {
                        $email = $this->emailDesambiguado($email, $ci);
                    }

                    DB::table('usuarios')->insert([
                        'ci_usuario'       => $ci,
                        'primer_nombre'    => $pn,
                        'segundo_nombre'   => $sn,
                        'primer_apellido'  => $pa,
                        'segundo_apellido' => $sa,
                        'email'            => $email,
                        'telefono'         => $telefono,
                        'password'         => Hash::make($ci),  // temporal = CI
                        'estado_registro'  => $estadoUsuario,    // 'aprobado'
                        'rol'              => 'socio',
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }
            }

            return $this->respuesta($request, true, "Solicitud #{$id} aprobada y usuario habilitado para login.");
        });
    }

    private function cambiarEstadoSolicitudYUsuario(Request $request, int $id, string $estadoSolicitud, ?string $estadoUsuario)
    {
        if (!in_array($estadoSolicitud, ['aprobado', 'rechazado'], true)) {
            return $this->respuesta($request, false, 'Estado destino inválido.');
        }

        return DB::transaction(function () use ($request, $id, $estadoSolicitud, $estadoUsuario) {
            $sol = DB::table('solicitudes')->lockForUpdate()->where('id', $id)->first();
            if (!$sol) {
                return $this->respuesta($request, false, 'Solicitud no encontrada.', 404);
            }

            $estadoActual = Str::lower($sol->estado ?? 'pendiente');
            if ($estadoActual !== 'pendiente') {
                return $this->respuesta($request, false, 'Solo se pueden cambiar solicitudes pendientes.', 409);
            }

            DB::table('solicitudes')->where('id', $id)->update([
                'estado'     => $estadoSolicitud,
                'updated_at' => now(),
            ]);

            if ($estadoUsuario !== null) {
                $ci = $this->extraerCiUsuario($sol);
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

    // -------- utilitarios --------

    private function extraerCiUsuario(object $solicitud): ?string
    {
        $raw = null;
        if (!empty($solicitud->ci_usuario)) $raw = (string) $solicitud->ci_usuario;
        elseif (!empty($solicitud->ci))     $raw = (string) $solicitud->ci;

        if ($raw === null) return null;
        return preg_replace('/[.\-\s]/', '', $raw); // normalizar CI
    }

    private function prefer(object $o, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (isset($o->{$k}) && trim((string) $o->{$k}) !== '') {
                return trim((string) $o->{$k});
            }
        }
        return null;
    }

    private function splitNombre(?string $nombreCompleto): array
    {
        $nombreCompleto = trim((string) $nombreCompleto);
        if ($nombreCompleto === '') return [null, null, null, null];

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