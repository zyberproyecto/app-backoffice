@extends('layout')

@section('title', 'Comprobantes')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <h1 class="h3 mb-0">Comprobantes ({{ $estado ?? 'todos' }})</h1>
  <div class="d-flex gap-2">
    {{-- preservamos el tipo actual en los filtros de estado --}}
    <a href="{{ route('admin.comprobantes.index', ['estado'=>'pendiente','tipo'=>$tipo]) }}" class="btn btn-outline-secondary btn-sm">
      Pendientes ({{ $resumen['pendientes'] ?? 0 }})
    </a>
    <a href="{{ route('admin.comprobantes.index', ['estado'=>'aprobado','tipo'=>$tipo]) }}"  class="btn btn-outline-success btn-sm">
      Aprobados ({{ $resumen['aprobados'] ?? 0 }})
    </a>
    <a href="{{ route('admin.comprobantes.index', ['estado'=>'rechazado','tipo'=>$tipo]) }}" class="btn btn-outline-danger btn-sm">
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
            // Si archivo es /storage/... generamos URL absoluta con base configurable
            $base = rtrim($filesBase ?? env('COOP_API_FILES_BASE','http://127.0.0.1:8002'), '/');
            $href = $c->archivo ?? '';
            if ($href && str_starts_with($href, '/')) { $href = $base . $href; }

            // Mostrar tipo más amigable
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

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function urlValidar(id){  return `/admin/comprobantes/${id}/validar`; }
    function urlRechazar(id){ return `/admin/comprobantes/${id}/rechazar`; }

    table.addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-action]');
      if (!btn) return;

      const tr = btn.closest('tr');
      const id = tr?.getAttribute('data-id');
      const action = btn.getAttribute('data-action'); // aprobar | rechazar
      if (!id || !action) return;

      let body = {};
      if (action === 'rechazar') {
        const motivo = prompt('Motivo de rechazo (opcional):');
        body.motivo = motivo || '';
      }

      const endpoint = action === 'aprobar' ? urlValidar(id) : urlRechazar(id);

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
          return;
        }

        // recargar manteniendo filtros
        window.location.reload();

      } catch (err) {
        console.error(err);
        alert('Error de red al ejecutar la acción.');
      }
    });
  })();
</script>
@endsection