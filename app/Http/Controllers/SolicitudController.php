<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SolicitudController extends Controller
{

    public function index(Request $request)
    {
        $estado = $request->query('estado', 'pendiente');

        $q = Solicitud::query();

        if (in_array($estado, ['pendiente','aprobado','rechazado'], true)) {
            $q->where('estado', $estado);
        }

        $solicitudes = $q->orderByRaw("FIELD(estado,'pendiente','aprobado','rechazado')")
            ->orderByDesc('created_at')
            ->paginate(20);

            return view('solicitudes.index', [
            'solicitudes' => $solicitudes,
            'items'       => $solicitudes,
            'estado'      => $estado,
        ]);
    }

    public function show($id)
    {
        $sol = Solicitud::findOrFail($id);
        return view('solicitudes.show', compact('sol'));
    }

    public function aprobar(Request $request, $id)
    {
          $adminId = auth('admin')->id() ?? auth()->id();

        $res = DB::transaction(function () use ($id, $adminId, $request) {
            $sol = Solicitud::lockForUpdate()->findOrFail($id);

            if ($sol->estado === 'aprobado') {
                return [
                    'ok' => true,
                    'msg' => 'La solicitud ya estaba aprobada.',
                    'temp_password' => null,
                    'usuario_ci' => $sol->usuario_ci ?? null
                ];
            }
            if ($sol->estado === 'rechazado') {
                return [
                    'ok' => false,
                    'msg' => 'La solicitud ya fue rechazada anteriormente.',
                    'temp_password' => null
                ];
            }

            $ci = $sol->ci ? preg_replace('/\D/', '', $sol->ci) : null;
            [$nombre, $apellido] = $this->splitNombre($sol->nombre_completo);

            $user = null;
            if ($ci) $user = Usuario::where('ci_usuario', $ci)->first();
            if (!$user && $sol->email) $user = Usuario::where('email', $sol->email)->first();

            $tempPassword = null;
            if (!$user) {
                $tempPassword = $this->passwordFuerte($nombre);
                $user = new Usuario();
                $user->ci_usuario      = $ci ?: $this->fakeCi();
                $user->primer_nombre   = $nombre ?: 'Socio';
                $user->primer_apellido = $apellido ?: 'Zyber';
                $user->email           = $sol->email ?: $this->fakeEmail();
                $user->telefono        = $sol->telefono ?? '';
                $user->password        = Hash::make($tempPassword);
                $user->rol             = 'socio';
            } else {
                if (!$user->primer_nombre && $nombre)     $user->primer_nombre = $nombre;
                if (!$user->primer_apellido && $apellido) $user->primer_apellido = $apellido;
                if (!$user->email && $sol->email)         $user->email = $sol->email;
                if (!$user->telefono && $sol->telefono)   $user->telefono = $sol->telefono;
                if (!$user->password) {
                    $tempPassword = $this->passwordFuerte($nombre);
                    $user->password = Hash::make($tempPassword);
                }
                if (!$user->rol) $user->rol = 'socio';
            }

            $user->estado_registro = 'aprobado';
            $user->save();

            if (Schema::hasColumn('solicitudes', 'usuario_ci')) {
                $sol->usuario_ci = $user->ci_usuario;
            }

            $sol->estado       = 'aprobado';
            $sol->aprobado_por = $adminId;
            $sol->aprobado_at  = now();
            $sol->save();

            if (Schema::hasTable('usuarios_perfil')) {
                $perfilData = [
                    'updated_at' => now(),
                ];

                if (Schema::hasColumn('usuarios_perfil', 'contacto')) $perfilData['contacto'] = $sol->telefono ?? '';
                if (Schema::hasColumn('usuarios_perfil', 'direccion')) $perfilData['direccion'] = $sol->direccion ?? '';
                if (Schema::hasColumn('usuarios_perfil', 'ocupacion')) $perfilData['ocupacion'] = $sol->ocupacion ?? '';
                if (Schema::hasColumn('usuarios_perfil', 'ingresos_nucleo_familiar')) $perfilData['ingresos_nucleo_familiar'] = (int)($sol->ingresos_nucleo_familiar ?? 0);
                if (Schema::hasColumn('usuarios_perfil', 'integrantes_familia')) $perfilData['integrantes_familia'] = (int)($sol->integrantes_familia ?? 0);
                if (Schema::hasColumn('usuarios_perfil', 'acepta_declaracion_jurada')) $perfilData['acepta_declaracion_jurada'] = (int)($sol->acepta_declaracion_jurada ?? 0);
                if (Schema::hasColumn('usuarios_perfil', 'acepta_reglamento_interno')) $perfilData['acepta_reglamento_interno'] = (int)($sol->acepta_reglamento_interno ?? 0);
                if (Schema::hasColumn('usuarios_perfil', 'estado_revision')) $perfilData['estado_revision'] = 'pendiente';
                if (Schema::hasColumn('usuarios_perfil', 'aprobado_por')) $perfilData['aprobado_por'] = null;
                if (Schema::hasColumn('usuarios_perfil', 'aprobado_at'))  $perfilData['aprobado_at']  = null;

                $exists = DB::table('usuarios_perfil')
                    ->where('ci_usuario', $user->ci_usuario)
                    ->exists();

                if ($exists) {
                    DB::table('usuarios_perfil')
                        ->where('ci_usuario', $user->ci_usuario)
                        ->update($perfilData);
                } else {
                    if (Schema::hasColumn('usuarios_perfil', 'created_at')) {
                        $perfilData['created_at'] = now();
                    }
                    DB::table('usuarios_perfil')->insert(array_merge(
                        ['ci_usuario' => $user->ci_usuario],
                        $perfilData
                    ));
                }
            }

            if ($request->filled('unidad_id') && Schema::hasTable('usuario_unidad')) {
                DB::table('usuario_unidad')->updateOrInsert(
                    ['ci_usuario' => $user->ci_usuario, 'unidad_id' => $request->unidad_id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            return [
                'ok' => true,
                'msg' => 'Solicitud aprobada y usuario habilitado.',
                'temp_password' => $tempPassword,
                'usuario_ci' => $user->ci_usuario
            ];
        });

        return redirect()
            ->route('admin.solicitudes.show', $id)
            ->with($res['ok'] ? 'ok' : 'error', $res['msg'])
            ->with('temp_password', $res['temp_password'])
            ->with('usuario_ci', $res['usuario_ci'] ?? null);
    }

     public function rechazar(Request $request, $id)
    {
        $adminId = auth('admin')->id() ?? auth()->id();

        $res = DB::transaction(function () use ($id, $adminId, $request) {
            $sol = Solicitud::lockForUpdate()->findOrFail($id);

            if ($sol->estado === 'aprobado') {
                return ['ok' => false, 'msg' => 'No se puede rechazar: ya está aprobada.'];
            }
            if ($sol->estado === 'rechazado') {
                return ['ok' => true, 'msg' => 'La solicitud ya estaba rechazada.'];
            }

            $sol->estado       = 'rechazado';
            $sol->aprobado_por = $adminId;
            $sol->aprobado_at  = now();

            if (Schema::hasColumn('solicitudes', 'nota_admin') && $request->filled('nota')) {
                $sol->nota_admin = $request->input('nota');
            }

            $sol->save();
            return ['ok' => true, 'msg' => 'Solicitud rechazada.'];
        });

        return redirect()
            ->route('admin.solicitudes.show', $id)
            ->with($res['ok'] ? 'ok' : 'error', $res['msg']);
    }

    private function splitNombre(?string $nombreCompleto): array
    {
        $nombreCompleto = trim((string)$nombreCompleto);
        if ($nombreCompleto === '') return [null, null];
        $partes = preg_split('/\s+/', $nombreCompleto, 2);
        return [$partes[0] ?? null, $partes[1] ?? null];
    }

    private function passwordFuerte(?string $nombre): string
{
    $base = strtolower(preg_replace('/[^a-z]/', '', \Illuminate\Support\Str::ascii((string)$nombre)));

    $pref = substr($base, 0, 3) ?: 'usr';

    return $pref . '123';
}
    private function fakeCi(): string
    {
        return (string)(now()->format('YmdHis') . rand(10, 99));
    }

    private function fakeEmail(): string
    {
        return 'sin-email-' . now()->format('YmdHis') . '@zyber.test';
    }
}