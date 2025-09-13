<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Backoffice — Cooperativa')</title>

  {{-- CSRF para peticiones desde JS --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Hook opcional por vista (por si querés inyectar CSS extra) --}}
  @yield('head')

  {{-- CSS desde public/css --}}
  <link rel="stylesheet" href="{{ asset('css/estilos.css') }}?v={{ @filemtime(public_path('css/estilos.css')) }}">
</head>
@php
  /** @var \App\Models\Admin|null $admin */
  $admin = auth('admin')->user();
@endphp
<body class="bo-body">

  <header class="bo-header">
    <div class="bo-header__brand">
      <a href="{{ route('dashboard') }}" style="color:inherit; text-decoration:none;">
        Backoffice — Cooperativa
      </a>
    </div>
    @if($admin)
      <nav class="d-flex align-items-center gap-3">
        <span class="text-sm text-muted">Sesión: <strong>{{ $admin->nombre_completo ?? $admin->ci_usuario }}</strong></span>
        {{-- Logout: POST /logout con CSRF --}}
        <button type="button" id="logout" class="bo-btn bo-btn--ghost">Cerrar sesión</button>
      </nav>
    @endif
  </header>

  <div class="bo-shell">
    @if($admin)
      <aside class="bo-sidebar">
        <nav class="bo-nav">
          <div>
            <div class="bo-nav__title">Socios</div>
            <a class="bo-nav__link" href="{{ route('admin.solicitudes.index') }}">Nuevos socios</a>
          </div>

          <div>
            <div class="bo-nav__title">Pagos</div>
            <a class="bo-nav__link" href="{{ route('admin.comprobantes.index', ['tipo' => 'inicial']) }}">Comprobante inicial</a>
            <a class="bo-nav__link" href="{{ route('admin.comprobantes.index', ['tipo' => 'mensual']) }}">Comprobantes mensuales</a>
          </div>

          <div>
            <div class="bo-nav__title">Trabajo</div>
            <a class="bo-nav__link" href="{{ route('admin.horas.index') }}">Horas de trabajo</a>
            <a class="bo-nav__link" href="{{ route('admin.exoneraciones.index') }}">Exoneraciones</a>
          </div>

          <div>
            <div class="bo-nav__title">Obra</div>
            <a class="bo-nav__link" href="{{ url('/admin/unidades') }}">Unidades</a>
          </div>
        </nav>
      </aside>
    @endif

    <main class="bo-main">
      {{-- Flash/messages globales --}}
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
      @endif
      @if ($errors->any())
        <div class="alert alert-danger">
          {{ $errors->first() }}
        </div>
      @endif

      @yield('content')
    </main>
  </div>

  {{-- Script mínimo para logout con CSRF --}}
  @if($admin)
  <script>
    (function () {
      const btn = document.getElementById('logout');
      if (!btn) return;

      btn.addEventListener('click', function () {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = @json(route('logout'));

        const csrf = document.createElement('input');
        csrf.type  = 'hidden';
        csrf.name  = '_token';
        csrf.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        form.appendChild(csrf);

        document.body.appendChild(form);
        form.submit();
      });
    })();
  </script>
  @endif

  {{-- Hook para scripts por página (si querés @push('scripts') en vistas) --}}
  @stack('scripts')
</body>
</html>