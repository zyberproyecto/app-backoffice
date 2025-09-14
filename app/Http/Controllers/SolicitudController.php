<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SolicitudController extends Controller
{
    // GET admin/solicitudes
    public function index()
    {
        $solicitudes = Solicitud::where('estado', 'pendiente')
            ->orderByDesc('created_at')
            ->get();

        // Si tu BO es API devuelve JSON; si es Blade, devuelve vista:
        // return view('admin.solicitudes.index', compact('solicitudes'));
        return response()->json(['ok' => true, 'solicitudes' => $solicitudes]);
    }

    // PUT admin/solicitudes/{id}/aprobar
    public function aprobar($id)
    {
        DB::transaction(function () use ($id) {
            // Bloqueo la fila para evitar doble aprobación concurrente
            $sol = Solicitud::lockForUpdate()->findOrFail($id);
            if ($sol->estado === 'aprobado') {
                return; // No hacemos nada si ya estaba aprobada
            }

            // 1) Normalizar CI a solo dígitos
            $ci = $sol->ci ? preg_replace('/\D/', '', $sol->ci) : null;

            // 2) Separar nombre/apellido muy básico desde nombre_completo
            [$nombre, $apellido] = $this->splitNombre($sol->nombre_completo);

            // 3) Buscar/crear usuario
            $user = null;

            if ($ci) {
                $user = Usuario::find($ci);
            }

            // Si no lo encontramos por CI, intento por email
            if (!$user && $sol->email) {
                $user = Usuario::where('email', $sol->email)->first();
            }

            if (!$user) {
                // Crear nuevo usuario
                $user = new Usuario();
                $user->ci_usuario       = $ci ?: $this->fakeCi(); // fallback si no vino CI
                $user->primer_nombre    = $nombre ?: 'Socio';
                $user->primer_apellido  = $apellido ?: 'Zyber';
                $user->email            = $sol->email ?? $this->fakeEmail();
                $user->password         = Hash::make($this->passwordSugerida($nombre));
                $user->rol              = 'socio';
            } else {
                // Completar datos faltantes si vinieron en la solicitud
                if (!$user->primer_nombre && $nombre)   $user->primer_nombre = $nombre;
                if (!$user->primer_apellido && $apellido) $user->primer_apellido = $apellido;
                if (!$user->email && $sol->email)       $user->email = $sol->email;
            }

            // Habilitar login
            $user->estado_registro = 'aprobado';
            $user->save();

            // 4) Marcar solicitud aprobada con trazabilidad
            $sol->estado       = 'aprobado';
            $sol->aprobado_por = auth('admin')->id() ?? auth()->id();
            $sol->aprobado_at  = now();
            $sol->save();
        });

        // Si es Blade:
        // return redirect()->route('admin.solicitudes.index')->with('ok','Solicitud aprobada y usuario habilitado.');
        return response()->json(['ok' => true, 'message' => 'Solicitud aprobada y usuario habilitado.']);
    }

    // PUT admin/solicitudes/{id}/rechazar
    public function rechazar($id)
    {
        $sol = Solicitud::findOrFail($id);
        if ($sol->estado !== 'rechazado') {
            $sol->estado       = 'rechazado';
            $sol->aprobado_por = auth('admin')->id() ?? auth()->id();
            $sol->aprobado_at  = now();
            $sol->save();
        }

        // Si es Blade:
        // return redirect()->route('admin.solicitudes.index')->with('ok','Solicitud rechazada.');
        return response()->json(['ok' => true, 'message' => 'Solicitud rechazada.']);
    }

    // ---- Helpers privados ----

    private function splitNombre(?string $nombreCompleto): array
    {
        $nombreCompleto = trim((string) $nombreCompleto);
        if ($nombreCompleto === '') return [null, null];

        $partes = preg_split('/\s+/', $nombreCompleto, 2);
        $nombre = $partes[0] ?? null;
        $apellido = $partes[1] ?? null;
        return [$nombre, $apellido];
    }

    private function passwordSugerida(?string $nombre): string
    {
        $base = strtolower(mb_substr($nombre ?: 'socio', 0, 3));
        return ($base !== '' ? $base : 'socio') . '123'; // ej: val123
    }

    private function fakeCi(): string
    {
        // Fallback SI Y SOLO SI no vino CI: timestamp recortado, evita colisión en dev.
        return (string) now()->format('YmdHis');
    }

    private function fakeEmail(): string
    {
        return 'sin-email-' . now()->format('YmdHis') . '@zyber.test';
    }
}