@extends('layout')

@section('title', 'Horas de trabajo — Backoffice')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h1 class="h3 mb-1">Horas de trabajo</h1>
    <p class="text-muted mb-0">Listado de cargas semanales de los socios.</p>
  </div>

  {{-- Filtro por CI --}}
  <form method="GET" action="{{ route('admin.horas.index') }}" class="d-flex gap-2">
    <input
      type="text"
      name="ci"
      class="form-control form-control-sm"
      placeholder="Filtrar por CI"
      value="{{ $ci }}"
      style="max-width: 180px"
    >
    <input type="hidden" name="estado" value="{{ $estado }}">
    <button class="btn btn-sm btn-outline-primary" type="submit">Buscar</button>
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.horas.index') }}">Limpiar</a>
  </form>
</div>

{{-- Filtros por estado --}}
<p class="mb-3">
  Estado:
  <a href="{{ route('admin.horas.index', ['estado'=>'todos','ci'=>$ci]) }}" class="{{ $estado==='todos' ? 'fw-bold' : '' }}">Todos</a> ·
  <a href="{{ route('admin.horas.index', ['estado'=>'pendiente','ci'=>$ci]) }}" class="{{ $estado==='pendiente' ? 'fw-bold' : '' }}">
    Pendientes ({{ $resumen['pendientes'] ?? 0 }})
  </a> ·
  <a href="{{ route('admin.horas.index', ['estado'=>'aprobado','ci'=>$ci]) }}" class="{{ $estado==='aprobado' ? 'fw-bold' : '' }}">
    Aprobadas ({{ $resumen['aprobadas'] ?? 0 }})
  </a> ·
  <a href="{{ route('admin.horas.index', ['estado'=>'rechazado','ci'=>$ci]) }}" class="{{ $estado==='rechazado' ? 'fw-bold' : '' }}">
    Rechazadas ({{ $resumen['rechazadas'] ?? 0 }})
  </a>
</p>

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm">
  <div class="table-responsive">
    <table id="tabla-horas" class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>CI</th>
          <th>Semana</th>
          <th>Fecha</th>
          <th>Horas</th>
          <th>Actividad</th>
          <th>Descripción</th>
          <th>Estado</th>
          <th>Creado</th>
          <th style="width:220px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $row)
          <tr data-id="{{ $row->id }}">
            <td>{{ $row->id }}</td>
            <td>{{ $row->ci_usuario }}</td>
            <td>{{ $row->semana ?? '—' }}</td>
            <td>{{ $row->fecha ?? '—' }}</td>
            <td>{{ $row->horas ?? '—' }}</td>
            <td>{{ $row->actividad ?? '—' }}</td>
            <td class="text-wrap" style="max-width:280px;">{{ $row->descripcion ?? '—' }}</td>
            <td class="text-nowrap">{{ ucfirst($row->estado) }}</td>
            <td>{{ \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i') }}</td>
            <td class="text-nowrap">
              @if(strtolower(trim($row->estado ?? '')) === 'pendiente')
                <button type="button" class="btn btn-sm btn-success" data-action="validar">Aprobar</button>
                <button type="button" class="btn btn-sm btn-danger"  data-action="rechazar">Rechazar</button>
              @else
                <span class="badge bg-secondary">Sin acciones</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="10" class="text-center text-muted py-4">Sin registros.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- JS inline: rutas WEB del backoffice + CSRF + anti doble click --}}
<script>
  (function(){
    const table = document.getElementById('tabla-horas');
    if (!table) return;

    const CSRF = @json(csrf_token());

    function urlValidar(id){  return `/admin/horas/${id}/validar`; }
    function urlRechazar(id){ return `/admin/horas/${id}/rechazar`; }

    table.addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-action]');
      if (!btn) return;

      const tr = btn.closest('tr');
      const id = tr?.getAttribute('data-id');
      const action = btn.getAttribute('data-action'); // validar | rechazar
      if (!id || !action) return;

      const confirmMsg = action === 'validar'
        ? `¿Confirmás aprobar el registro de horas #${id}?`
        : `¿Confirmás rechazar el registro de horas #${id}?`;
      if (!confirm(confirmMsg)) return;

      let body = {};
      if (action === 'rechazar') {
        const motivo = prompt('Motivo de rechazo (opcional):');
        body.motivo = (motivo || '').trim();
      }

      const endpoint = action === 'validar' ? urlValidar(id) : urlRechazar(id);

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