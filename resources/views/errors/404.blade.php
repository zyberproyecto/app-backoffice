<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>404 — Página no encontrada</title>
  <link rel="stylesheet" href="{{ asset('css/estilos.css') }}?v={{ @filemtime(public_path('css/estilos.css')) }}">
</head>
<body style="padding:2rem">
  <h1>404 — Página no encontrada</h1>
  <p>La ruta solicitada no existe.</p>
  <p><a class="bo-link" href="{{ route('dashboard') }}">Volver al dashboard</a></p>
</body>
</html>