<?php
namespace App\Http\Controllers;

use App\Models\HoraTrabajo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;

class HorasAdminController extends Controller
{
    // POST /api/horas
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'semana_inicio'    => ['required','date'],
            'horas_reportadas' => ['required','numeric','min:0','max:168'],
            'motivo'           => ['nullable','string'],
        ]);

        $ini = Carbon::parse($data['semana_inicio'])->startOfWeek(Carbon::MONDAY);
        $fin = (clone $ini)->endOfWeek(Carbon::SUNDAY);

        try {
            $row = HoraTrabajo::create([
                'ci_usuario'       => $user->ci_usuario,
                'semana_inicio'    => $ini->toDateString(),
                'semana_fin'       => $fin->toDateString(),
                'horas_reportadas' => $data['horas_reportadas'],
                'motivo'           => $data['motivo'] ?? null,
                'estado'           => HoraTrabajo::ESTADO_REPORTADO,
            ]);
        } catch (QueryException $e) {
            return response()->json(['ok' => false, 'error' => 'Ya reportaste horas para esa semana.'], 422);
        }

        return response()->json(['ok' => true, 'hora' => $row], 201);
    }

    // GET /api/horas/mias?desde=YYYY-MM-DD&hasta=YYYY-MM-DD
    public function mias(Request $request)
    {
        $user  = $request->user();
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $q = HoraTrabajo::where('ci_usuario', $user->ci_usuario)->orderByDesc('semana_inicio');
        if ($desde) $q->whereDate('semana_inicio', '>=', $desde);
        if ($hasta) $q->whereDate('semana_inicio', '<=', $hasta);

        return response()->json(['ok' => true, 'items' => $q->get()]);
    }
}