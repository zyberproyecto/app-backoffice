@extends('layout')
@section('title','Perfil — CI '.$perfil->ci_usuario)

@section('content')
<div class="bo-main">
  <h1 class="bo-h1">Perfil — CI {{ $perfil->ci_usuario }}</h1>

  @if(session('ok'))    <div class="bo-alert bo-alert--success">{{ session('ok') }}</div>@endif
  @if(session('error')) <div class="bo-alert" style="background:#fef2f2;color:#991b1b;border-color:#fecaca;">{{ session('error') }}</div>@endif

  <div class="bo-panel">
    <div class="bo-panel__title">Datos personales</div>

    @php
      $st = strtolower($perfil->estado_revision ?? 'incompleto');
      $fmtMoney = fn($v) => is_null($v) ? '—' : ('$ '.number_format((float)$v, 2, ',', '.'));
    @endphp

    <div class="bo-panel__body">
      <dl style="display:grid; grid-template-columns:180px 1fr; gap:8px 16px; margin:0;">
        <dt class="bo-muted">CI</dt>
        <dd>{{ $perfil->ci_usuario }}</dd>

        <dt class="bo-muted">Nombre</dt>
        <dd>{{ isset($usuario) ? trim(($usuario->primer_nombre ?? '').' '.($usuario->primer_apellido ?? '')) ?: '—' : '—' }}</dd>

        <dt class="bo-muted">Email</dt>
        <dd>{{ $usuario->email ?? '—' }}</dd>

        <dt class="bo-muted">Teléfono</dt>
        <dd>{{ $usuario->telefono ?? '—' }}</dd>

        <dt class="bo-muted">Ocupación</dt>
        <dd>{{ $perfil->ocupacion ?? '—' }}</dd>

        <dt class="bo-muted">Ingresos (núcleo)</dt>
        <dd>{{ $fmtMoney($perfil->ingresos_nucleo_familiar ?? null) }}</dd>

        <dt class="bo-muted">Integrantes</dt>
        <dd>{{ $perfil->integrantes_familia ?? '—' }}</dd>

        <dt class="bo-muted">Contacto alternativo</dt>
        <dd>{{ $perfil->contacto ?? '—' }}</dd>

        <dt class="bo-muted">Dirección</dt>
        <dd>{{ $perfil->direccion ?? '—' }}</dd>

        <dt class="bo-muted">Estado</dt>
        <dd>
          <span style="padding:2px 8px; border-radius:999px; font-size:.85rem;
            @switch($st)
              @case('aprobado')  background:#ecfdf5;color:#065f46; @break
              @case('rechazado') background:#fef2f2;color:#991b1b; @break
              @default           background:#fff7ed;color:#9a3412;
            @endswitch
          ">{{ $st }}</span>
        </dd>

        <dt class="bo-muted">Actualizado</dt>
        <dd>{{ !empty($perfil->updated_at) ? \Illuminate\Support\Carbon::parse($perfil->updated_at)->format('Y-m-d H:i') : '—' }}</dd>

        <dt class="bo-muted">Aprobado por</dt>
        <dd>{{ $perfil->aprobado_por ?? '—' }}</dd>

        <dt class="bo-muted">Aprobado el</dt>
        <dd>{{ !empty($perfil->aprobado_at) ? \Illuminate\Support\Carbon::parse($perfil->aprobado_at)->format('Y-m-d H:i') : '—' }}</dd>
      </dl>
    </div>

    <div class="bo-panel__footer" style="display:flex; gap:8px; justify-content:flex-end;">
      <a href="{{ route('admin.perfiles.index') }}" class="bo-btn bo-btn--ghost">Volver</a>

      @if($st === 'pendiente')
        <form action="{{ route('admin.perfiles.aprobar', $perfil->ci_usuario) }}" method="POST">
          @csrf @method('PUT')
          <button type="submit" class="bo-btn bo-btn--success">Aprobar</button>
        </form>
        <form action="{{ route('admin.perfiles.rechazar', $perfil->ci_usuario) }}" method="POST">
          @csrf @method('PUT')
          <button type="submit" class="bo-btn bo-btn--danger">Rechazar</button>
        </form>
      @endif
    </div>
  </div>
</div>
@endsection