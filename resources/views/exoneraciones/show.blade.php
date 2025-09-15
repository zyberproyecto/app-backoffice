@extends('layout')
@section('title','Exoneración #'.$exo->id)

@section('content')
<div style="display:grid; grid-template-columns: 1.6fr .8fr; gap:12px;">

  {{-- Detalle --}}
  <section class="bo-panel">
    <div class="bo-panel__title">Exoneración #{{ $exo->id }}</div>
    <div>
      <dl style="margin:0; display:grid; grid-template-columns: 160px 1fr; gap:10px 12px;">
        <dt class="bo-muted">CI</dt>
        <dd style="margin:0;">{{ $exo->ci_usuario }}</dd>

        <dt class="bo-muted">Semana</dt>
        <dd style="margin:0;">{{ $exo->semana_inicio }}</dd>

        <dt class="bo-muted">Motivo</dt>
        <dd style="margin:0;">{{ $exo->motivo }}</dd>

        <dt class="bo-muted">Estado</dt>
        <dd style="margin:0;">
          @if($exo->estado === 'pendiente')
            <span style="color:var(--bo-warning); font-weight:600;">Pendiente</span>
          @elseif($exo->estado === 'aprobada')
            <span style="color:var(--bo-success); font-weight:600;">Aprobada</span>
          @else
            <span class="bo-muted">Rechazada</span>
          @endif
        </dd>
      </dl>

      @isset($hora)
        <hr style="margin:16px 0;">
        <h6 style="margin:0 0 6px 0; font-weight:700;">Horas de la semana</h6>
        <p class="bo-muted" style="margin:0;">
          Reportadas: <strong>{{ $hora->horas_reportadas }}</strong> —
          Estado: <strong>{{ $hora->estado }}</strong>
        </p>
      @endisset
    </div>
  </section>

  {{-- Acciones --}}
  <aside class="bo-panel">
    <div class="bo-panel__title">Acciones</div>
    <div>
      @if($exo->estado==='pendiente')
        <form method="POST" action="{{ route('admin.exoneraciones.aprobar',$exo->id) }}" style="margin:0 0 8px 0;">
          @csrf @method('PUT')
          <button class="bo-btn" type="submit" style="width:100%;">Aprobar</button>
        </form>

        <form method="POST" action="{{ route('admin.exoneraciones.rechazar',$exo->id) }}" style="margin:0;">
          @csrf @method('PUT')
          {{-- 
          <label class="bo-muted" for="nota">Motivo (opcional)</label>
          <textarea class="bo-input mb-2" id="nota" name="nota" rows="2" placeholder="Motivo del rechazo"></textarea>
          --}}
          <button class="bo-btn bo-btn--ghost" type="submit" style="width:100%;">Rechazar</button>
        </form>

      @elseif($exo->estado==='aprobada')
        <div class="bo-alert bo-alert--success">Aprobada.</div>
      @else
        <div class="bo-alert">Rechazada.</div>
      @endif

      <div style="margin-top:12px;">
        <a class="bo-link" href="{{ route('admin.exoneraciones.index') }}">← Volver</a>
      </div>
    </div>
  </aside>

</div>
@endsection