@extends('layout')
@section('title','Backoffice | Iniciar sesión')

@section('content')
<div class="bo-login">
  <div class="bo-login__card">
    <div class="text-center mb-2">
      <div aria-hidden="true" style="font-size:2rem; line-height:1; margin-bottom:.25rem;">🔐</div>
      <h1 class="bo-h1" style="margin-bottom:.25rem;">Iniciar sesión</h1>
      <p class="bo-muted" style="margin:0;">Acceso al Backoffice de la cooperativa</p>
    </div>

    @if(session('error'))
      <div class="bo-alert bo-alert--error mb-2">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" novalidate>
      @csrf

      <div class="bo-form__group">
        <label for="login" class="bo-muted">CI o Email</label>
        <input
          type="text"
          id="login"
          name="login"
          value="{{ old('login') }}"
          required
          class="bo-input"
          autocomplete="username"
          inputmode="text"
          autofocus
          @error('login') aria-invalid="true" @enderror
        >
        @error('login')
          <div style="color:#991b1b; font-size:.9rem; margin-top:4px;">{{ $message }}</div>
        @enderror
      </div>

      <div class="bo-form__group">
        <label for="password" class="bo-muted">Contraseña</label>

        <div style="position:relative;">
          <input
            type="password"
            id="password"
            name="password"
            required
            class="bo-input"
            autocomplete="current-password"
            @error('password') aria-invalid="true" @enderror
          >
          {{-- Botón mostrar/ocultar (posicionado a la derecha del input) --}}
          <button
            type="button"
            class="toggle-pass"
            aria-label="Mostrar u ocultar contraseña"
            style="
              position:absolute; right:8px; top:50%; transform:translateY(-50%);
              background:transparent; border:0; cursor:pointer; padding:4px 6px; font-size:.9rem; color:#475569;
            "
          >👁️</button>
        </div>

        @error('password')
          <div style="color:#991b1b; font-size:.9rem; margin-top:4px;">{{ $message }}</div>
        @enderror
      </div>

      <button type="submit" class="bo-btn" style="width:100%;">Entrar</button>
      <div class="bo-muted" style="margin-top:.5rem; font-size:.9rem;">
        Ingresá la CI <strong>sin puntos ni guiones</strong>.
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (function () {
    const btn = document.querySelector('.toggle-pass');
    const input = document.getElementById('password');
    if (!btn || !input) return;
    const update = () => btn.classList.toggle('on', input.type === 'text');
    btn.addEventListener('click', () => {
      input.type = input.type === 'password' ? 'text' : 'password';
      update();
    });
    update();
  })();
</script>
@endpush