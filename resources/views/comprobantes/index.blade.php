@extends('layout')

@section('title', 'Comprobantes')

@section('content')
@php
    use Illuminate\Support\Facades\Storage;

    // helpers del filtro
    $tipoSel   = $tipo ?? null; // 'inicial' | 'mensual' | null
    $estadoSel = $estado ?? 'pendiente';
@endphp

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h1 class="h3 mb-1">Comprobantes</h1>
    <div class="text-muted">Estado: <strong>{{ $estadoSel }}</strong>@if($tipoSel) · Tipo: <strong>{{ $tipoSel }}</strong>@endif</div>
  </div>

  <div class="d-flex gap-2">
    {{-- Filtros de TIPO --}}
    <div class="btn-group me-2" role="group" aria-label="Filtros tipo">
      <a href="{{ route('admin.comprobantes.index', ['estado'=>$estadoSel]) }}"
         class="btn btn-sm {{ $tipoSel ? 'btn-outline-secondary' : 'btn-secondary' }}">Todos</a>
      <a href="{{ route('admin.comprobantes.index', ['estado'=>$estadoSel,'tipo'=>'inicial']) }}"
         class="btn btn-sm {{ $tipoSel==='inicial' ? 'btn-secondary' : 'btn-outline-secondary' }}">Inicial</a>
      <a href="{{ route('admin.comprobantes.index', ['estado'=>$estadoSel,'tipo'=>'mensual']) }}"
         class="btn btn-sm {{ $tipoSel==='mensual' ? 'btn-secondary' : 'btn-outline-secondary' }}">Mensual</a>
    </div>

    {{-- Filtros de ESTADO (preservan tipo actual) --}}
    <a href="{{ route('admin.comprobantes.index', ['estado'=>'pendiente','tipo'=>$tipoSel]) }}" class="btn btn-outline-secondary btn-sm">
      Pendientes ({{ $resumen['pendientes'] ?? 0 }})
    </a>
    <a href="{{ route('admin.comprobantes.index', ['estado'=>'aprobado','tipo'=>$tipoSel]) }}"  class="btn btn-outline-success btn-sm">
      Aprobados ({{ $resumen['aprobados'] ?? 0 }})
    </a>
    <a href="{{ route('admin.comprobantes.index', ['estado'=>'rechazado','tipo'=>$tipoSel]) }}" class="btn btn-outline-danger btn-sm">
      Rechazados ({{ $resumen['rechazados'] ?? 0 }})
    </a>
  </div>
</div>

@if(session('success')) 
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error')) 
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm">
  <div class="table-responsive">
    <table id="tabla-comprobantes" class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>CI</th>
          <th>Tipo</th>
          <th>Monto</th>
          <th>Fecha pago</th>
          <th>Estado</th>
          <th>Archivo</th>
          <th>Nota/Motivo</th>
          <th>Creado</th>
          <th style="width:240px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $c)
          @php
            // URL del archivo (preferir Storage::url si es ruta relativa de storage)
            $href = $c->archivo ?? '';
            if ($href) {
              if (preg_match('#^https?://#i', $href)) {
                // URL absoluta ya válida
              } elseif (str_starts_with($href, '/')) {
                $href = asset(ltrim($href, '/'));
              } else {
                // p.ej: "comprobantes/123.pdf" en storage/app/public/...
                $href = Storage::url($href);
              }
            }
            $tipoLabel = $c->tipo === 'aporte_inicial' ? 'inicial' : $c->tipo;
          @endphp
          <tr data-id="{{ $c->id }}">
            <td>{{ $c->id }}</td>
            <td>{{ $c->ci_usuario }}</td>
            <td>{{ $tipoLabel }}</td>
            <td>{{ is_null($c->monto) ? '—' : $c->monto }}</td>
            <td>{{ $c->fecha_pago ?? '—' }}</td>
            <td>{{ ucfirst($c->estado) }}</td>
            <td>
              @if($c->archivo)
                <a href="{{ $href }}" target="_blank" rel="noopener">Ver archivo</a>
              @else
                —
              @endif
            </td>
            <td class="text-wrap" style="max-width:260px;">{{ $c->nota_admin ?? '—' }}</td>
            <td>{{ \Carbon\Carbon::parse($c->created_at)->format('Y-m-d H:i') }}</td>
            <td class="text-nowrap">
              @if(strtolower(trim($c->estado ?? '')) === 'pendiente')
                <button type="button" class="btn btn-sm btn-success" data-action="aprobar">Aprobar</button>
                <button type="button" class="btn btn-sm btn-danger"  data-action="rechazar">Rechazar</button>
              @else
                <span class="badge bg-secondary">Sin acciones</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="10" class="text-center text-muted py-4">Sin comprobantes.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- JS inline: usa rutas WEB del backoffice + CSRF --}}
<script>
  (function(){
    const table = document.getElementById('tabla-comprobantes');
    if (!table) return;

    const CSRF = @json(csrf_token());

    function urlValidar(id){  return `/admin/comprobantes/${id}/validar`; }
    function urlRechazar(id){ return `/admin/comprobantes/${id}/rechazar`; }

    table.addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-action]');
      if (!btn) return;

      const tr = btn.closest('tr');
      const id = tr?.getAttribute('data-id');
      const action = btn.getAttribute('data-action'); // aprobar | rechazar
      if (!id || !action) return;

      // pedir motivo (OBLIGATORIO) al rechazar
      let body = {};
      if (action === 'rechazar') {
        let motivo = '';
        do {
          motivo = prompt('Motivo de rechazo (obligatorio):');
          if (motivo === null) return; // cancelado
          motivo = (motivo || '').trim();
          if (!motivo) alert('El motivo es obligatorio para rechazar.');
        } while (!motivo);
        body.motivo = motivo;
      }

      const endpoint = action === 'aprobar' ? urlValidar(id) : urlRechazar(id);

      // anti doble click
      btn.disabled = true;

      try {
        const resp = await fetch(endpoint, {
          method: 'PUT',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin',
          body: JSON.stringify(body)
        });

        const data = await resp.json().catch(() => ({}));

        if (!resp.ok || data.ok === false) {
          alert(data?.message || 'No se pudo completar la acción.');
          btn.disabled = false;
          return;
        }

        // recargar manteniendo filtros
        window.location.reload();

      } catch (err) {
        console.error(err);
        alert('Error de red al ejecutar la acción.');
        btn.disabled = false;
      }
    });
  })();
</script>
@endsection