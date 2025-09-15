@extends('layout')
@section('title','Unidad '.$u->codigo)

@section('content')
<div style="display:grid; grid-template-columns: 1.4fr .8fr; gap:16px;">

  {{-- Detalle de la unidad --}}
  <section class="bo-panel">
    <div class="bo-panel__title">Unidad {{ $u->codigo }}</div>

    <dl style="margin:0; display:grid; grid-template-columns: 180px 1fr; gap:10px 12px;">
      <dt class="bo-muted">Código</dt>
      <dd style="margin:0;">{{ $u->codigo }}</dd>

      <dt class="bo-muted">Descripción</dt>
      <dd style="margin:0;">{{ $u->descripcion ?? '—' }}</dd>

      <dt class="bo-muted">Dormitorios</dt>
      <dd style="margin:0;">{{ $u->dormitorios ?? '—' }}</dd>

      <dt class="bo-muted">Superficie</dt>
      <dd style="margin:0;">{{ $u->m2 ? number_format($u->m2,2,',','.') . ' m²' : '—' }}</dd>

      <dt class="bo-muted">Estado</dt>
      <dd style="margin:0;">
        @if($u->estado_unidad === 'disponible')
          <span class="bo-muted" style="font-weight:600;">Disponible</span>
        @elseif($u->estado_unidad === 'asignada')
          <span style="color:var(--bo-warning); font-weight:600;">Asignada</span>
        @elseif($u->estado_unidad === 'entregada')
          <span style="color:var(--bo-success); font-weight:600;">Entregada</span>
        @else
          <span class="bo-muted">{{ ucfirst($u->estado_unidad) }}</span>
        @endif
      </dd>

      <dt class="bo-muted">Creada</dt>
      <dd style="margin:0;">{{ optional($u->created_at)->format('Y-m-d H:i') ?? '—' }}</dd>

      <dt class="bo-muted">Actualizada</dt>
      <dd style="margin:0;">{{ optional($u->updated_at)->format('Y-m-d H:i') ?? '—' }}</dd>
    </dl>

    {{-- Asignación actual, si existe --}}
    @isset($asignacion)
      <hr style="margin:16px 0;">
      <h6 style="margin:0 0 6px 0; font-weight:700;">Asignación actual</h6>
      <p class="bo-muted" style="margin:0;">
        Socio: <strong>{{ trim(($asignacion->primer_nombre ?? '').' '.($asignacion->primer_apellido ?? '')) }}</strong>
        (CI: <strong>{{ $asignacion->ci_usuario }}</strong>) —
        Desde: <strong>{{ \Illuminate\Support\Carbon::parse($asignacion->fecha_asignacion)->format('d/m/Y') }}</strong>
      </p>
      @if(!empty($asignacion->nota_admin))
        <p class="bo-muted" style="margin:.5rem 0 0;">Nota: {{ $asignacion->nota_admin }}</p>
      @endif
    @endisset
  </section>

  {{-- Acciones --}}
  <aside class="bo-panel">
    <div class="bo-panel__title">Acciones</div>

    @if(session('ok'))
      <div class="bo-alert bo-alert--success" style="margin-bottom:10px;">{{ session('ok') }}</div>
    @endif
    @if(session('error'))
      <div class="bo-alert bo-alert--error" style="margin-bottom:10px;">{{ session('error') }}</div>
    @endif

    {{-- Si está disponible → asignar --}}
    @if($u->estado_unidad === 'disponible')
      <form method="POST" action="{{ route('admin.unidades.asignar') }}" style="margin:0 0 12px 0;">
        @csrf
        <input type="hidden" name="unidad_id" value="{{ $u->id }}">
        <div class="bo-form__group">
          <label class="bo-muted" for="ci_usuario">CI del socio</label>
          <input
            id="ci_usuario"
            name="ci_usuario"
            type="text"
            class="bo-input"
            placeholder="CI (7–8 díg.)"
            required
            inputmode="numeric"
            pattern="[0-9]{7,8}"
            title="Ingrese 7 u 8 dígitos"
          >
        </div>
        <div class="bo-form__group">
          <label class="bo-muted" for="nota_admin">Nota (opcional)</label>
          <input id="nota_admin" name="nota_admin" type="text" class="bo-input" placeholder="Observación interna">
        </div>
        <button class="bo-btn" type="submit" style="width:100%;">Asignar</button>
      </form>

    {{-- Si está asignada → liberar --}}
    @elseif($u->estado_unidad === 'asignada')
      <form method="POST" action="{{ route('admin.unidades.liberar', $u->id) }}" style="margin:0;">
        @csrf
        @method('PUT')
        <button class="bo-btn bo-btn--ghost" type="submit" style="width:100%;">Liberar unidad</button>
      </form>

    {{-- Si está entregada → solo info --}}
    @elseif($u->estado_unidad === 'entregada')
      <div class="bo-alert">La unidad está marcada como <strong>entregada</strong>. No hay acciones disponibles.</div>
    @else
      <div class="bo-alert">Estado desconocido. Verificá la configuración.</div>
    @endif

    <div style="margin-top:12px;">
      <a class="bo-link" href="{{ route('admin.unidades.index') }}">← Volver</a>
    </div>
  </aside>
</div>

{{-- Historial de asignaciones (opcional) --}}
@if(isset($historial) && $historial->count())
  <div class="bo-panel" style="margin-top:16px;">
    <div class="bo-panel__title">Historial de asignaciones</div>
    <div style="overflow-x:auto;">
      <table class="bo-table">
        <thead>
          <tr>
            <th>CI</th>
            <th>Socio</th>
            <th>Desde</th>
            <th>Hasta</th>
            <th>Nota</th>
          </tr>
        </thead>
        <tbody>
          @foreach($historial as $h)
            <tr>
              <td>{{ $h->ci_usuario }}</td>
              <td>{{ trim(($h->primer_nombre ?? '').' '.($h->primer_apellido ?? '')) }}</td>
              <td>{{ \Illuminate\Support\Carbon::parse($h->fecha_asignacion)->format('d/m/Y') }}</td>
              <td>
                {{ $h->fecha_liberacion
                    ? \Illuminate\Support\Carbon::parse($h->fecha_liberacion)->format('d/m/Y')
                    : '—' }}
              </td>
              <td>{{ $h->nota_admin ?? '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif
@endsection