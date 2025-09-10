<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Backoffice — Cooperativa')</title>

  {{-- CSRF para peticiones desde JS --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- CSS desde public/css --}}
  <link rel="stylesheet" href="{{ asset('css/estilos.css') }}?v={{ @filemtime(public_path('css/estilos.css')) }}">

  {{-- JS global (si consumís APIs con Bearer). Defer para no bloquear. --}}
  <script src="{{ asset('js/boot.js') }}" defer></script>
</head>
<body class="bo-body">

  <header class="bo-header">
    <div class="bo-header__brand">
      <a href="{{ route('dashboard') }}" style="color:inherit; text-decoration:none;">
        Backoffice — Cooperativa
      </a>
    </div>
    <nav>
      {{-- Logout: dispara POST /logout con CSRF y además limpia localStorage en la vista logout --}}
      <button type="button" id="logout" class="bo-btn bo-btn--ghost">Cerrar sesión</button>
    </nav>
  </header>

  <div class="bo-shell">
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
          <a class="bo-nav__link" href="{{ url('/admin/horas') }}">Horas de trabajo</a>
          <a class="bo-nav__link" href="{{ url('/admin/exoneraciones') }}">Exoneraciones</a>
        </div>

        <div>
          <div class="bo-nav__title">Obra</div>
          <a class="bo-nav__link" href="{{ url('/admin/unidades') }}">Unidades</a>
        </div>
      </nav>
    </aside>

    <main class="bo-main">
      @yield('content')
    </main>
  </div>

  {{-- Script mínimo para logout con CSRF --}}
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
</body>
</html>