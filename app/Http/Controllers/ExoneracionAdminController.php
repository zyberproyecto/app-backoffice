<?php
namespace App\Http\Controllers;

use App\Models\Exoneracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExoneracionAdminController extends Controller
{
    public function index()
    {
        $items = Exoneracion::where('estado', Exoneracion::ESTADO_PENDIENTE)
            ->orderByDesc('semana_inicio')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['ok' => true, 'pendientes' => $items]);
        // o return view('admin.exoneraciones.index', compact('items'));
    }

    public function validar($id, Request $request)
    {
        DB::transaction(function () use ($id, $request) {
            $e = Exoneracion::lockForUpdate()->findOrFail($id);
            $e->estado = Exoneracion::ESTADO_APROBADA;
            if ($request->filled('resolucion_admin')) {
                $e->resolucion_admin = $request->input('resolucion_admin');
            }
            $e->save();
        });

        return response()->json(['ok' => true, 'message' => 'Exoneración aprobada']);
    }

    public function rechazar($id, Request $request)
    {
        DB::transaction(function () use ($id, $request) {
            $e = Exoneracion::lockForUpdate()->findOrFail($id);
            $e->estado = Exoneracion::ESTADO_RECHAZADA;
            if ($request->filled('resolucion_admin')) {
                $e->resolucion_admin = $request->input('resolucion_admin');
            }
            $e->save();
        });

        return response()->json(['ok' => true, 'message' => 'Exoneración rechazada']);
    }
}