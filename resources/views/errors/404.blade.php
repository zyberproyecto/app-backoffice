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

  <div style="padding:2rem">
    <h1>404 — Página no encontrada</h1>
    <p>La ruta solicitada no existe o fue movida.</p>
    <p>
      <a class="bo-link" href="{{ $backUrl }}">{{ $backTxt }}</a>
    </p>

    @if(config('app.debug'))
      {{-- En desarrollo, mostrar info mínima útil --}}
      <p style="margin-top:1rem;color:#666;font-size:.9rem;">
        <strong>Debug:</strong> {{ request()->method() }} {{ request()->fullUrl() }}
      </p>
    @endif
  </div>
@endsection