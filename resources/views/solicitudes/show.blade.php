@extends('layout')
@section('title','Solicitud #'.$sol->id)

@section('content')
<div style="display:grid; grid-template-columns: 1.6fr .8fr; gap:12px;">

  {{-- Detalle --}}
  <section class="bo-panel">
    <div class="bo-panel__title">Solicitud #{{ $sol->id }}</div>
    <div>
      <dl style="margin:0; display:grid; grid-template-columns: 180px 1fr; gap:10px 12px;">
        <dt class="bo-muted">CI</dt>
        <dd style="margin:0;">{{ $sol->ci }}</dd>

        <dt class="bo-muted">Nombre completo</dt>
        <dd style="margin:0;">{{ $sol->nombre_completo }}</dd>

        <dt class="bo-muted">Email</dt>
        <dd style="margin:0;">{{ $sol->email }}</dd>

        <dt class="bo-muted">Teléfono</dt>
        <dd style="margin:0;">{{ $sol->telefono }}</dd>

        <dt class="bo-muted">Dormitorios</dt>
        <dd style="margin:0;">{{ $sol->dormitorios }}</dd>

        <dt class="bo-muted">Menores a cargo</dt>
        <dd style="margin:0;">{{ (int)$sol->menores_a_cargo ? 'Sí' : 'No' }}</dd>

        <dt class="bo-muted">Comentarios</dt>
        <dd style="margin:0;">{{ $sol->comentarios }}</dd>

        <dt class="bo-muted">Estado</dt>
        <dd style="margin:0;">
          @if($sol->estado === 'pendiente')
            <span style="color:var(--bo-warning); font-weight:600;">Pendiente</span>
          @elseif($sol->estado === 'aprobado')
            <span style="color:var(--bo-success); font-weight:600;">Aprobado</span>
          @else
            <span class="bo-muted">Rechazado</span>
          @endif
        </dd>

        <dt class="bo-muted">Aprobado por</dt>
        <dd style="margin:0;">{{ $sol->aprobado_por ?? '-' }}</dd>

        <dt class="bo-muted">Aprobado el</dt>
        <dd style="margin:0;">{{ optional($sol->aprobado_at)->format('Y-m-d H:i') ?? '-' }}</dd>
      </dl>
    </div>
  </section>

  {{-- Acciones --}}
  <aside class="bo-panel">
    <div class="bo-panel__title">Acciones</div>
    <div>
      @if(session('temp_password'))
        <div class="bo-alert bo-alert--info" style="margin-bottom:10px;">
          <strong>Contraseña temporal:</strong> <code>{{ session('temp_password') }}</code>
        </div>
      @endif

      @if($sol->estado === 'pendiente')
        <form method="POST" action="{{ route('admin.solicitudes.aprobar',$sol->id) }}" style="margin:0 0 8px 0;">
          @csrf @method('PUT')
          {{-- 
          <label class="bo-muted" for="unidad_id">Unidad (opcional)</label>
          <input type="number" class="bo-input mb-2" id="unidad_id" name="unidad_id" placeholder="ID Unidad">
          --}}
          <button class="bo-btn" type="submit" style="width:100%;">Aprobar y crear usuario</button>
        </form>

        <form method="POST" action="{{ route('admin.solicitudes.rechazar',$sol->id) }}" style="margin:0;">
          @csrf @method('PUT')
          {{-- 
          <label class="bo-muted" for="nota">Motivo (opcional)</label>
          <textarea class="bo-input mb-2" id="nota" name="nota" rows="2" placeholder="Motivo del rechazo"></textarea>
          --}}
          <button class="bo-btn bo-btn--ghost" type="submit" style="width:100%;">Rechazar</button>
        </form>

      @elseif($sol->estado === 'aprobado')
        <div class="bo-alert bo-alert--success">Esta solicitud ya fue aprobada.</div>
      @else
        <div class="bo-alert">Esta solicitud fue rechazada.</div>
      @endif

      <div style="margin-top:12px;">
        <a class="bo-link" href="{{ route('admin.solicitudes.index') }}">← Volver</a>
      </div>
    </div>
  </aside>

</div>
@endsection