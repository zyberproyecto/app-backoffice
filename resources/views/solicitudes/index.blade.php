@extends('layout')

@section('title', 'Solicitudes de ingreso — Backoffice')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h1 class="h3 mb-1">Solicitudes de ingreso</h1>
    <p class="text-muted mb-0">Listado de solicitudes que llegan desde la landing.</p>
  </div>
  {{-- (opcional) mantener un form hidden si querés preservar el valor del filtro por URL --}}
  <form method="GET" action="{{ route('admin.solicitudes.index') }}" class="d-none d-md-block">
    <input type="hidden" name="estado" value="{{ $estado }}">
  </form>
</div>

<p class="mb-3">
  Filtrar:
  <a href="{{ route('admin.solicitudes.index', ['estado'=>'todos']) }}" class="{{ $estado==='todos' ? 'fw-bold' : '' }}">Todos</a> ·
  <a href="{{ route('admin.solicitudes.index',['estado'=>'pendiente']) }}" class="{{ $estado==='pendiente' ? 'fw-bold' : '' }}">Pendientes ({{ $resumen['pendientes'] }})</a> ·
  <a href="{{ route('admin.solicitudes.index',['estado'=>'aprobada']) }}"  class="{{ $estado==='aprobada'  ? 'fw-bold' : '' }}">Aprobadas ({{ $resumen['aprobadas'] }})</a> ·
  <a href="{{ route('admin.solicitudes.index',['estado'=>'rechazada']) }}" class="{{ $estado==='rechazada' ? 'fw-bold' : '' }}">Rechazadas ({{ $resumen['rechazadas'] }})</a>
</p>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>CI</th>
          <th>Nombre</th>
          <th>Email</th>
          <th>Teléfono</th>
          <th>Menores</th>
          <th>Dormitorios</th>
          <th>Comentarios</th>
          <th>Estado</th>
          <th style="width:220px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $s)
          <tr data-id="{{ $s->id }}">
            <td>{{ $s->id }}</td>
            <td>{{ $s->ci_usuario }}</td>
            <td>{{ $s->nombre }}</td>
            <td>{{ $s->email }}</td>
            <td>{{ $s->telefono }}</td>
            <td>{{ (int)$s->menores_a_cargo > 0 ? 'Sí' : 'No' }}</td>
            <td>{{ $s->dormitorios }}</td>
            <td class="text-wrap" style="max-width:260px;">{{ $s->comentarios ?: '—' }}</td>
            <td class="text-nowrap">{{ ucfirst($s->estado) }}</td>
            <td class="text-nowrap">
              @if(strtolower(trim($s->estado ?? '')) === 'pendiente')
                <button type="button" class="btn btn-sm btn-success" data-action="aprobar">Aprobar</button>
                <button type="button" class="btn btn-sm btn-danger"  data-action="rechazar">Rechazar</button>
              @else
                <em>Sin acciones</em>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="10" class="text-center text-muted py-4">Sin solicitudes.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Script inline mínimo para acciones (usa rutas WEB del backoffice + CSRF) --}}
<script>
  (function() {
    const table = document.currentScript.closest('.card').querySelector('table');
    if (!table) return;

    // Token CSRF (desde Blade)
    const CSRF = @json(csrf_token());

    // Helpers: endpoints web del backoffice
    function urlAprobar(id)  { return `/admin/solicitudes/${id}/aprobar`; }
    function urlRechazar(id) { return `/admin/solicitudes/${id}/rechazar`; }

    table.addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-action]');
      if (!btn) return;

      const tr = btn.closest('tr');
      const id = tr?.getAttribute('data-id');
      const action = btn.getAttribute('data-action'); // aprobar | rechazar
      if (!id || !action) return;

      const confirmMsg = action === 'aprobar'
        ? `¿Confirmás aprobar la solicitud #${id}?`
        : `¿Confirmás rechazar la solicitud #${id}?`;

      if (!confirm(confirmMsg)) return;

      const endpoint = action === 'aprobar' ? urlAprobar(id) : urlRechazar(id);

      try {
        const resp = await fetch(endpoint, {
          method: 'PUT',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF
          },
          body: JSON.stringify({}) // el controlador no requiere body
        });

        const data = await resp.json().catch(()=>({}));
        if (!resp.ok || data.ok === false) {
          const msg = data?.message || 'No se pudo completar la acción.';
          alert(msg);
          return;
        }

        // Éxito: recargar la página manteniendo el filtro actual
        window.location.reload();
      } catch (err) {
        console.error(err);
        alert('Error de red al ejecutar la acción.');
      }
    });
  })();
</script>
@endsection