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
    // GET /admin/solicitudes
    public function index()
    {
        $solicitudes = Solicitud::orderByRaw("FIELD(estado,'pendiente','aprobado','rechazado')")
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('solicitudes.index', compact('solicitudes'));
    }

    // GET /admin/solicitudes/{id}
    public function show($id)
    {
        $sol = Solicitud::findOrFail($id);
        return view('solicitudes.show', compact('sol'));
    }

    // PUT /admin/solicitudes/{id}/aprobar
    public function aprobar(Request $request, $id)
    {
        $adminId = auth()->id();

        $res = DB::transaction(function () use ($id, $adminId, $request) {
            $sol = Solicitud::lockForUpdate()->findOrFail($id);

            if ($sol->estado === 'aprobado') {
                return ['ok'=>true,'msg'=>'La solicitud ya estaba aprobada.','temp_password'=>null,'usuario_ci'=>$sol->usuario_ci ?? null];
            }
            if ($sol->estado === 'rechazado') {
                return ['ok'=>false,'msg'=>'La solicitud ya fue rechazada anteriormente.','temp_password'=>null];
            }

            // Normalizar CI (sólo dígitos)
            $ci = $sol->ci ? preg_replace('/\D/','',$sol->ci) : null;

            // Partir nombre/apellido básico
            [$nombre, $apellido] = $this->splitNombre($sol->nombre_completo);

            // Buscar usuario por CI o email
            $user = null;
            if ($ci) $user = Usuario::where('ci_usuario', $ci)->first();
            if (!$user && $sol->email) $user = Usuario::where('email',$sol->email)->first();

            $tempPassword = null;
            if (!$user) {
                $tempPassword = $this->passwordFuerte($nombre);
                $user = new Usuario();
                $user->ci_usuario      = $ci ?: $this->fakeCi();
                $user->primer_nombre   = $nombre ?: 'Socio';
                $user->primer_apellido = $apellido ?: 'Zyber';
                $user->email           = $sol->email ?: $this->fakeEmail();
                $user->telefono        = $sol->telefono ?? $user->telefono;
                $user->password        = Hash::make($tempPassword);
                $user->rol             = $user->rol ?: 'socio';
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

            if (Schema::hasColumn('solicitudes','usuario_ci')) {
                $sol->usuario_ci = $user->ci_usuario;
            }

            $sol->estado       = 'aprobado';
            $sol->aprobado_por = $adminId;
            $sol->aprobado_at  = now();
            $sol->save();

            // (Opcional) Asignar unidad si vino en el form
            if ($request->filled('unidad_id') && Schema::hasTable('usuario_unidad')) {
                DB::table('usuario_unidad')->updateOrInsert(
                    ['ci_usuario'=>$user->ci_usuario,'unidad_id'=>$request->unidad_id],
                    ['created_at'=>now(),'updated_at'=>now()]
                );
            }

            return ['ok'=>true,'msg'=>'Solicitud aprobada y usuario habilitado.','temp_password'=>$tempPassword,'usuario_ci'=>$user->ci_usuario];
        });

        return redirect()
            ->route('admin.solicitudes.show', $id)
            ->with($res['ok'] ? 'ok' : 'error', $res['msg'])
            ->with('temp_password', $res['temp_password'])
            ->with('usuario_ci', $res['usuario_ci'] ?? null);
    }

    // PUT /admin/solicitudes/{id}/rechazar
    public function rechazar(Request $request, $id)
    {
        $adminId = auth()->id();

        $res = DB::transaction(function () use ($id, $adminId, $request) {
            $sol = Solicitud::lockForUpdate()->findOrFail($id);

            if ($sol->estado === 'aprobado') {
                return ['ok'=>false,'msg'=>'No se puede rechazar: ya está aprobada.'];
            }
            if ($sol->estado === 'rechazado') {
                return ['ok'=>true,'msg'=>'La solicitud ya estaba rechazada.'];
            }

            $sol->estado       = 'rechazado';
            $sol->aprobado_por = $adminId;
            $sol->aprobado_at  = now();

            if (Schema::hasColumn('solicitudes','nota_admin') && $request->filled('nota')) {
                $sol->nota_admin = $request->input('nota');
            }

            $sol->save();
            return ['ok'=>true,'msg'=>'Solicitud rechazada.'];
        });

        return redirect()
            ->route('admin.solicitudes.show', $id)
            ->with($res['ok'] ? 'ok' : 'error', $res['msg']);
    }

    // ==== Helpers ====
    private function splitNombre(?string $nombreCompleto): array
    {
        $nombreCompleto = trim((string)$nombreCompleto);
        if ($nombreCompleto === '') return [null,null];
        $partes = preg_split('/\s+/', $nombreCompleto, 2);
        return [$partes[0] ?? null, $partes[1] ?? null];
    }

    private function passwordFuerte(?string $nombre): string
    {
        $pref = $nombre ? ucfirst(mb_substr(preg_replace('/\W+/u','',$nombre),0,3)) : 'Soc';
        return $pref.'-'.Str::random(8);
    }

    private function fakeCi(): string
    {
        return (string)(now()->format('YmdHis').rand(10,99));
    }

    private function fakeEmail(): string
    {
        return 'sin-email-'.now()->format('YmdHis').'@zyber.test';
    }
}