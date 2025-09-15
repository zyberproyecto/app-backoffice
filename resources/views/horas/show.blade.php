@extends('layout')
@section('title','Horas #'.$row->id)

@section('content')
<div style="display:grid; grid-template-columns: 1.6fr .8fr; gap:12px;">

  {{-- Detalle --}}
  <section class="bo-panel">
    <div class="bo-panel__title">Horas #{{ $row->id }}</div>
    <div>
      <dl style="margin:0; display:grid; grid-template-columns: 180px 1fr; gap:10px 12px;">
        <dt class="bo-muted">CI</dt>
        <dd style="margin:0;">{{ $row->ci_usuario }}</dd>

        <dt class="bo-muted">Semana</dt>
        <dd style="margin:0;">{{ $row->semana_inicio }} – {{ $row->semana_fin }}</dd>

        <dt class="bo-muted">Horas reportadas</dt>
        <dd style="margin:0;">{{ $row->horas_reportadas }}</dd>

        <dt class="bo-muted">Motivo</dt>
        <dd style="margin:0;">{{ $row->motivo }}</dd>

        <dt class="bo-muted">Estado</dt>
        <dd style="margin:0;">
          @if($row->estado === 'reportado')
            <span style="color:var(--bo-warning); font-weight:600;">Reportado</span>
          @elseif($row->estado === 'aprobado')
            <span style="color:var(--bo-success); font-weight:600;">Aprobado</span>
          @else
            <span class="bo-muted">Rechazado</span>
          @endif
        </dd>
      </dl>

      @isset($exoneracion)
        <hr style="margin:16px 0;">
        <h6 style="margin:0 0 6px 0; font-weight:700;">Exoneración vinculada</h6>
        <p class="bo-muted" style="margin:0;">
          Estado: <strong>{{ $exoneracion->estado }}</strong>
          — Motivo: {{ $exoneracion->motivo }}
        </p>
      @endisset
    </div>
  </section>

  {{-- Acciones --}}
  <aside class="bo-panel">
    <div class="bo-panel__title">Acciones</div>
    <div>
      @if($row->estado==='reportado')
        <form method="POST" action="{{ route('admin.horas.aprobar',$row->id) }}" style="margin:0 0 8px 0;">
          @csrf @method('PUT')
          <button class="bo-btn" type="submit" style="width:100%;">Aprobar</button>
        </form>

        <form method="POST" action="{{ route('admin.horas.rechazar',$row->id) }}" style="margin:0;">
          @csrf @method('PUT')
          <button class="bo-btn bo-btn--ghost" type="submit" style="width:100%;">Rechazar</button>
        </form>

      @elseif($row->estado==='aprobado')
        <div class="bo-alert bo-alert--success">Aprobado.</div>
      @else
        <div class="bo-alert">Rechazado.</div>
      @endif

      <div style="margin-top:12px;">
        <a class="bo-link" href="{{ route('admin.horas.index') }}">← Volver</a>
      </div>
    </div>
  </aside>

</div>
@endsection