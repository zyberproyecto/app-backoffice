@extends('layout')
@section('title','Backoffice | Iniciar sesión')

@section('content')
<div style="max-width:420px;margin:3rem auto;">
  <h1 style="font-size:1.25rem;margin-bottom:1rem;">Backoffice — Iniciar sesión</h1>

  <form method="POST" action="{{ route('login.post') }}" novalidate>
    @csrf
    <div class="mb-3">
      <label for="login">CI o Email</label>
      <input
        type="text"
        id="login"
        name="login"
        value="{{ old('login') }}"
        required
        class="form-control"
        autocomplete="username"
        inputmode="text"
        autofocus
      >
    </div>
    <div class="mb-3">
      <label for="password">Contraseña</label>
      <input
        type="password"
        id="password"
        name="password"
        required
        class="form-control"
        autocomplete="current-password"
      >
    </div>
    <button type="submit" class="btn btn-primary w-100">Entrar</button>
  </form>
</div>
@endsection