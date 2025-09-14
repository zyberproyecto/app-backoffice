<?php
namespace App\Http\Controllers;

use App\Models\Comprobante;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;

class ComprobanteController extends Controller
{
    // POST /api/comprobantes  (multipart/form-data)
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'tipo'    => ['required', Rule::in([
                Comprobante::TIPO_APORTE_INICIAL,
                Comprobante::TIPO_APORTE_MENSUAL,
                Comprobante::TIPO_COMPENSATORIO,
            ])],
            // Requerir periodo salvo en aporte_inicial
            'periodo' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/', Rule::requiredIf(fn() => $request->tipo !== Comprobante::TIPO_APORTE_INICIAL)],
            'monto'   => ['required','numeric','min:0'],
            'archivo' => ['required','file','mimes:pdf,jpg,jpeg,png','max:5120'], // 5MB
        ]);

        // Guardar archivo en storage/app/public/comprobantes/<ci>/...
        $path = $request->file('archivo')->store("comprobantes/{$user->ci_usuario}", 'public');

        try {
            $row = Comprobante::create([
                'ci_usuario' => $user->ci_usuario,
                'tipo'       => $data['tipo'],
                'periodo'    => $data['periodo'] ?? null,
                'monto'      => $data['monto'],
                'archivo'    => $path,
                'estado'     => Comprobante::ESTADO_PENDIENTE,
            ]);
        } catch (QueryException $e) {
            // Colisión por UNIQUE (ci_usuario, tipo, periodo)
            return response()->json([
                'ok' => false,
                'error' => 'Ya existe un comprobante de ese tipo para ese período.'
            ], 422);
        }

        return response()->json(['ok' => true, 'comprobante' => $row], 201);
    }

    // GET /api/comprobantes/estado?tipo=&periodo=
    public function estado(Request $request)
    {
        $user   = $request->user();
        $tipo   = $request->query('tipo');   // opcional
        $periodo= $request->query('periodo'); // opcional (YYYY-MM)

        $q = Comprobante::where('ci_usuario', $user->ci_usuario)->orderByDesc('created_at');
        if ($tipo)    $q->where('tipo', $tipo);
        if ($periodo) $q->where('periodo', $periodo);

        return response()->json(['ok' => true, 'items' => $q->get()]);
    }
}
