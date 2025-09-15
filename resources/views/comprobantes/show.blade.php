@extends('layout')
@section('title','Comprobante #'.$row->id)

@section('content')
<div style="display:grid; grid-template-columns: 1.6fr .8fr; gap:12px;">

  {{-- Detalle --}}
  <section class="bo-panel">
    <div class="bo-panel__title">Comprobante #{{ $row->id }}</div>
    <div>

      <dl style="margin:0; display:grid; grid-template-columns: 160px 1fr; gap:10px 12px;">
        <dt class="bo-muted">CI</dt>
        <dd style="margin:0;">{{ $row->ci_usuario }}</dd>

        <dt class="bo-muted">Tipo</dt>
        <dd style="margin:0;">{{ $row->tipo }}</dd>

        <dt class="bo-muted">Periodo</dt>
        <dd style="margin:0;">{{ $row->periodo }}</dd>

        <dt class="bo-muted">Monto</dt>
        <dd style="margin:0;">{{ number_format((float)($row->monto ?? 0),2,',','.') }}</dd>

        <dt class="bo-muted">Estado</dt>
        <dd style="margin:0;">
          @if($row->estado === 'pendiente')
            <span style="color:var(--bo-warning); font-weight:600;">Pendiente</span>
          @elseif($row->estado === 'aprobado')
            <span style="color:var(--bo-success); font-weight:600;">Aprobado</span>
          @else
            <span class="bo-muted">{{ ucfirst($row->estado) }}</span>
          @endif
        </dd>

        @if(isset($row->archivo) || isset($row->archivo_url))
          <dt class="bo-muted">Archivo</dt>
          <dd style="margin:0;">
            @if(isset($row->archivo_url))
              <a href="{{ $row->archivo_url }}" target="_blank" rel="noopener">Ver archivo</a>
            @else
              <code>{{ $row->archivo }}</code>
            @endif
          </dd>
        @endif
      </dl>

    </div>
  </section>

  {{-- Acciones --}}
  <aside class="bo-panel">
    <div class="bo-panel__title">Acciones</div>
    <div>

      @if($row->estado==='pendiente')
        <form method="POST" action="{{ route('admin.comprobantes.aprobar',$row->id) }}" style="margin:0 0 8px 0;">
          @csrf @method('PUT')
          <button class="bo-btn" type="submit" style="width:100%;">Aprobar</button>
        </form>

        <form method="POST" action="{{ route('admin.comprobantes.rechazar',$row->id) }}" style="margin:0;">
          @csrf @method('PUT')
          {{-- Si querés habilitar motivo:
          <label class="bo-muted" for="nota">Motivo (opcional)</label>
          <textarea class="bo-input mb-2" id="nota" name="nota" rows="2" placeholder="Motivo del rechazo"></textarea>
          --}}
          <button class="bo-btn bo-btn--ghost" type="submit" style="width:100%;">Rechazar</button>
        </form>

      @elseif($row->estado==='aprobado')
        <div class="bo-alert bo-alert--success mb-2">Aprobado.</div>
      @else
        <div class="bo-alert mb-2">Rechazado.</div>
      @endif

      <div style="margin-top:12px;">
        <a href="{{ route('admin.comprobantes.index') }}">← Volver</a>
      </div>
    </div>
  </aside>

</div>
@endsection