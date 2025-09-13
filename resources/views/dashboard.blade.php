@extends('layout')

@section('title', 'Dashboard')

@section('content')
<div class="dashboard-container">

  <h1 class="section-title">Pendientes de validar</h1>
  <div class="cards-row">
    @php
      $c = (int)($pendientesComprobantes ?? 0);
      $h = (int)($pendientesHoras ?? 0);
      $e = (int)($pendientesExoneraciones ?? 0);
      $s = (int)($pendientesSocios ?? 0);

      $tC = $c === 1 ? '1 pendiente' : $c . ' pendientes';
      $tH = $h === 1 ? '1 pendiente' : $h . ' pendientes';
      $tE = $e === 1 ? '1 pendiente' : $e . ' pendientes';
      $tS = $s === 1 ? '1 pendiente' : $s . ' pendientes';
    @endphp

    <a class="dash-card" href="{{ route('admin.comprobantes.index', ['tipo' => 'mensual', 'estado' => 'pendiente']) }}">
      <div class="dash-card__title">Comprobantes de pago</div>
      <div class="dash-card__count">{{ $tC }}</div>
    </a>

    <a class="dash-card" href="{{ route('admin.horas.index', ['estado' => 'pendiente']) }}">
      <div class="dash-card__title">Horas de trabajo</div>
      <div class="dash-card__count">{{ $tH }}</div>
    </a>

    <a class="dash-card" href="{{ route('admin.exoneraciones.index', ['estado' => 'pendiente']) }}">
      <div class="dash-card__title">Exoneraciones</div>
      <div class="dash-card__count">{{ $tE }}</div>
    </a>

    <a class="dash-card" href="{{ route('admin.solicitudes.index', ['estado' => 'pendiente']) }}">
      <div class="dash-card__title">Nuevos socios</div>
      <div class="dash-card__count">{{ $tS }}</div>
    </a>
  </div>

  <div class="section-row">
    <div class="panel">
      <div class="panel__title">Novedades</div>
      <div class="panel__body">
        <div class="panel-empty">Sin novedades por ahora</div>
      </div>
    </div>
  </div>

  {{-- Módulo de gastos: opcional / futuro. Podés envolverlo en una flag de features. --}}
  @if(config('features.gastos', false))
    <div class="module">
      <div class="module__title">Módulo de gastos</div>
      <ul class="module-list">
        <li><a href="{{ url('/gastos/ingresar') }}">Ingresar gasto</a></li>
        <li><a href="{{ url('/gastos/facturas-a-pagar') }}">Facturas a pagar</a></li>
        <li><a href="{{ url('/gastos/facturas-pagas') }}">Facturas pagas</a></li>
        <li><a href="{{ url('/gastos/apuntes-contables') }}">Apuntes contables</a></li>
        <li><a href="{{ url('/gastos/reportes') }}">Emitir reporte financiero</a></li>
      </ul>
    </div>
  @endif

</div>
@endsection