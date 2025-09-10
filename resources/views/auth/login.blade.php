{{-- resources/views/auth/login.blade.php --}}
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Backoffice — Cooperativa | Iniciar sesión</title>

  {{-- Si tienes un CSS (opcional), déjalo así: --}}
  <link rel="stylesheet" href="{{ asset('css/estilos.css') }}">
</head>
<body>

  <main class="auth-wrapper" style="min-height:100vh; display:flex; align-items:center; justify-content:center; background:#0f172a;">
    <section class="auth-card" style="width:100%; max-width:520px; background:#0b1220; color:#e5e7eb; border-radius:14px; padding:28px; box-shadow:0 10px 30px rgba(0,0,0,.35)">

      <header style="margin-bottom:18px">
        <h1 style="margin:0; font-size:26px; letter-spacing:.3px">Iniciar sesión</h1>
        <p style="margin:6px 0 0; color:#94a3b8; font-size:14px">Acceso al panel de administración.</p>
      </header>

      {{-- Mensajes de error --}}
      @if ($errors->any())
        <div role="alert" style="background:#33141a; color:#fecaca; border:1px solid #7f1d1d; padding:10px 12px; border-radius:10px; margin-bottom:14px">
          {{ $errors->first() }}
        </div>
      @endif

      {{-- Mensaje flash opcional --}}
      @if (session('status'))
        <div role="alert" style="background:#0b2a1b; color:#a7f3d0; border:1px solid #065f46; padding:10px 12px; border-radius:10px; margin-bottom:14px">
          {{ session('status') }}
        </div>
      @endif

      <form method="POST" action="{{ route('login.post') }}" novalidate style="display:grid; gap:14px">
        @csrf

        <div>
          <label for="login" style="display:block; font-size:13px; color:#a3e635; margin-bottom:6px">CI o Email</label>
          <input
            type="text"
            id="login"
            name="login"
            value="{{ old('login') }}"
            placeholder="Ingresa tu CI o email"
            autocomplete="username"
            required
            style="width:100%; background:#0a0f1d; color:#e5e7eb; border:1px solid #1f2937; border-radius:10px; padding:12px 14px; outline:none">
        </div>

        <div>
          <label for="password" style="display:block; font-size:13px; color:#a3e635; margin-bottom:6px">Contraseña</label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Ingresa tu contraseña"
            autocomplete="current-password"
            required
            style="width:100%; background:#0a0f1d; color:#e5e7eb; border:1px solid #1f2937; border-radius:10px; padding:12px 14px; outline:none">
        </div>

        <button type="submit"
          style="margin-top:6px; background:#2563eb; color:#fff; border:none; border-radius:10px; padding:12px 16px; font-weight:600; cursor:pointer">
          Ingresar
        </button>
      </form>

    </section>
  </main>

</body>
</html>