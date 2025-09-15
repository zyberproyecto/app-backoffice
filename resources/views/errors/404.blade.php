@extends('layout')

@section('title', '404 — Página no encontrada')

@section('head')
  {{-- Evitar indexación de páginas de error --}}
  <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
@php
  $isAdmin = auth('admin')->check();
  $backUrl = $isAdmin ? route('dashboard') : route('login');
  $backTxt = $isAdmin ? 'Volver al dashboard' : 'Ir al login';
@endphp

<div class="bo-panel" style="max-width:600px; margin:2rem auto; text-align:center;">
  <h1 class="bo-h1">404 — Página no encontrada</h1>
  <p class="bo-muted">La ruta solicitada no existe o fue movida.</p>

  <p style="margin-top:1rem;">
    <a class="bo-btn" href="{{ $backUrl }}">{{ $backTxt }}</a>
  </p>

  @if(config('app.debug'))
    {{-- En desarrollo, mostrar info mínima útil --}}
    <p class="bo-muted" style="margin-top:1rem; font-size:.9rem;">
      <strong>Debug:</strong> {{ request()->method() }} {{ request()->fullUrl() }}
    </p>
  @endif
</div>
@endsection