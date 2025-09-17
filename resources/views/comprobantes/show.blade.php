@extends('layout')
@section('title','Comprobante #'.$row->id)

@section('content')
<div style="display:grid; grid-template-columns: 1.6fr .8fr; gap:12px;">

  {{-- Detalle --}}
  <section class="bo-panel">
    <div class="bo-panel__title">Comprobante #{{ $row->id }}</div>
    <div>
      @php
        $tipoVal = strtolower((string)($row->tipo ?? $row->tipo_aporte ?? ''));
        $tipoTxt = match(true) {
          in_array($tipoVal, ['inicial','aporte_inicial']) => 'Aporte inicial',
          $tipoVal === 'aporte_mensual' => 'Aporte mensual',
          $tipoVal === 'compensatorio'  => 'Compensatorio',
          default => $tipoVal !== '' ? ucfirst($tipoVal) : '—',
        };
        $estado  = strtolower((string)$row->estado);
        $archivo = $row->archivo_url ?? $row->archivo ?? null;
      @endphp

      <dl style="margin:0; display:grid; grid-template-columns: 160px 1fr; gap:10px 12px;">
        <dt class="bo-muted">CI</dt>
        <dd style="margin:0;">{{ $row->ci_usuario }}</dd>

        <dt class="bo-muted">Tipo</dt>
        <dd style="margin:0;">{{ $tipoTxt }}</dd>

        <dt class="bo-muted">Periodo</dt>
        <dd style="margin:0;">{{ $row->periodo ?? '—' }}</dd>

        <dt class="bo-muted">Monto</dt>
        <dd style="margin:0;">
          @if(isset($row->monto))
            {{ number_format((float)$row->monto,2,',','.') }}
          @else
            —
          @endif
        </dd>

        <dt class="bo-muted">Estado</dt>
        <dd style="margin:0;">
          @if($estado === 'pendiente')
            <span style="color:var(--bo-warning); font-weight:600;">Pendiente</span>
          @elseif($estado === 'aprobado')
            <span style="color:var(--bo-success); font-weight:600;">Aprobado</span>
          @elseif($estado === 'rechazado')
            <span class="bo-muted">Rechazado</span>
          @else
            <span class="bo-muted">{{ ucfirst($row->estado ?? '—') }}</span>
          @endif
        </dd>

        @if($archivo)
          <dt class="bo-muted">Archivo</dt>
          <dd style="margin:0;">
            <a href="{{ $archivo }}" target="_blank" rel="noopener">Ver archivo</a>
          </dd>
        @endif

        <dt class="bo-muted">Creado</dt>
        <dd style="margin:0;">{{ $row->created_at ?? '—' }}</dd>

        @if(!empty($row->aprobado_por))
          <dt class="bo-muted">Aprobado por</dt>
          <dd style="margin:0;">{{ $row->aprobado_por }}</dd>
        @endif

        @if(!empty($row->aprobado_at))
          <dt class="bo-muted">Aprobado el</dt>
          <dd style="margin:0;">{{ $row->aprobado_at }}</dd>
        @endif

        @if(!empty($row->nota_admin))
          <dt class="bo-muted">Nota admin</dt>
          <dd style="margin:0;">{{ $row->nota_admin }}</dd>
        @endif
      </dl>

      @if($archivo)
        <div style="margin-top:14px;">
          @php $isPdf = \Illuminate\Support\Str::endsWith(strtolower($archivo), '.pdf'); @endphp
          @if($isPdf)
            <iframe src="{{ $archivo }}" width="100%" height="420" style="border:1px solid #ddd; border-radius:6px;"></iframe>
          @else
            <img src="{{ $archivo }}" alt="Comprobante" style="max-width:100%; border:1px solid #ddd; border-radius:6px;">
          @endif
        </div>
      @endif

    </div>
  </section>

  {{-- Acciones --}}
  <aside class="bo-panel">
    <div class="bo-panel__title">Acciones</div>
    <div>
      @if($estado === 'pendiente')
        <form method="POST" action="{{ route('admin.comprobantes.aprobar',$row->id) }}" style="margin:0 0 8px 0;">
          @csrf
          <button class="bo-btn" type="submit" style="width:100%;">Aprobar</button>
        </form>

        <form method="POST" action="{{ route('admin.comprobantes.rechazar',$row->id) }}" style="margin:0;">
          @csrf
          <label class="bo-muted" for="nota">Motivo (opcional)</label>
          <textarea class="bo-input mb-2" id="nota" name="nota" rows="2" placeholder="Motivo del rechazo"></textarea>
          <button class="bo-btn bo-btn--ghost" type="submit" style="width:100%;">Rechazar</button>
        </form>
      @elseif($estado === 'aprobado')
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