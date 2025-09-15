@extends('layout')

@section('title', 'Dashboard')

@section('content')
<div class="dashboard-container">

  <h1 class="section-title">Pendientes de validar</h1>

  @php
    $ai  = (int)($pendientesAporteInicial  ?? 0);
    $m   = (int)($pendientesComprobantes   ?? 0);
    $cmp = (int)($pendientesCompensatorios ?? 0);
    $h   = (int)($pendientesHoras          ?? 0);
    $e   = (int)($pendientesExoneraciones  ?? 0);
    $s   = (int)($pendientesSocios         ?? 0);

    $np = function (int $n) { return $n === 1 ? '1 pendiente' : ($n.' pendientes'); };
  @endphp

  <div class="cards-row">

    <a class="dash-card"
       href="{{ route('admin.comprobantes.index', ['tipo' => 'inicial', 'estado' => 'pendiente']) }}"
       aria-label="Aporte inicial: {{ $np($ai) }}">
      <div class="dash-card__title">Aporte inicial</div>
      <div class="dash-card__count">{{ $np($ai) }}</div>
    </a>

    <a class="dash-card"
       href="{{ route('admin.comprobantes.index', ['tipo' => 'mensual', 'estado' => 'pendiente']) }}"
       aria-label="Comprobantes mensuales: {{ $np($m) }}">
      <div class="dash-card__title">Comprobantes mensuales</div>
      <div class="dash-card__count">{{ $np($m) }}</div>
    </a>

    <a class="dash-card"
       href="{{ route('admin.comprobantes.index', ['tipo' => 'compensatorio', 'estado' => 'pendiente']) }}"
       aria-label="Compensatorios: {{ $np($cmp) }}">
      <div class="dash-card__title">Compensatorios</div>
      <div class="dash-card__count">{{ $np($cmp) }}</div>
    </a>

    <a class="dash-card"
       href="{{ route('admin.horas.index', ['estado' => 'reportado']) }}"
       aria-label="Horas de trabajo: {{ $np($h) }}">
      <div class="dash-card__title">Horas de trabajo</div>
      <div class="dash-card__count">{{ $np($h) }}</div>
    </a>

    <a class="dash-card"
       href="{{ route('admin.exoneraciones.index', ['estado' => 'pendiente']) }}"
       aria-label="Exoneraciones: {{ $np($e) }}">
      <div class="dash-card__title">Exoneraciones</div>
      <div class="dash-card__count">{{ $np($e) }}</div>
    </a>

    <a class="dash-card"
       href="{{ route('admin.solicitudes.index', ['estado' => 'pendiente']) }}"
       aria-label="Nuevos socios: {{ $np($s) }}">
      <div class="dash-card__title">Nuevos socios</div>
      <div class="dash-card__count">{{ $np($s) }}</div>
    </a>

  </div>

  <div class="section-row">
    <section class="panel">
      <div class="panel__title">Novedades</div>
      <div class="panel__body">
        <div class="panel-empty bo-muted">Sin novedades por ahora</div>
      </div>
    </section>
  </div>

  {{-- Módulo de gastos: opcional / futuro --}}
  @if(config('features.gastos', false))
    <section class="module">
      <div class="module__title">Módulo de gastos</div>
      <ul class="module-list">
        <li><a href="{{ url('/gastos/ingresar') }}">Ingresar gasto</a></li>
        <li><a href="{{ url('/gastos/facturas-a-pagar') }}">Facturas a pagar</a></li>
        <li><a href="{{ url('/gastos/facturas-pagas') }}">Facturas pagas</a></li>
        <li><a href="{{ url('/gastos/apuntes-contables') }}">Apuntes contables</a></li>
        <li><a href="{{ url('/gastos/reportes') }}">Emitir reporte financiero</a></li>
      </ul>
    </section>
  @endif

</div>
@endsection