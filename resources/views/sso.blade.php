<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Conectando…</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Necesario para POST /session/start --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
    (async function () {
        const params = new URLSearchParams(window.location.search);
        const token  = (params.get("token") || "").trim();
        const next   = params.get("next");

        // Configurá en .env del backoffice: API_USUARIOS_BASE=http://127.0.0.1:8001
        const API_USUARIOS_BASE = "{{ env('API_USUARIOS_BASE', 'http://127.0.0.1:8001') }}";
        const DASHBOARD_URL     = "{{ route('dashboard') }}";

        const fail = (msg) => {
            try {
                localStorage.removeItem("token");
                localStorage.removeItem("zyber_token");
            } catch (e) {}
            document.body.innerHTML = "<p style='color:#b00'>" + msg + "</p>";
        };

        if (!token) {
            fail("No se recibió token válido.");
            return;
        }

        // Guardar token para fetch/axios desde el backoffice
        try {
            localStorage.setItem("token", token);
            localStorage.setItem("zyber_token", token); // compat
            localStorage.setItem("token_saved_at", new Date().toISOString());
        } catch (e) {
            console.error("No se pudo guardar el token:", e);
        }

        // Verificar token y ROL con /api/me
        let resp;
        try {
            resp = await fetch(`${API_USUARIOS_BASE}/api/me`, {
                headers: {
                    "Accept": "application/json",
                    "Authorization": `Bearer ${token}`
                }
            });
        } catch (e) {
            fail("No se pudo contactar a API-Usuarios.");
            return;
        }

        if (!resp.ok) {
            fail("Token inválido o expirado.");
            return;
        }

        const json = await resp.json();
        const rol  = json?.user?.rol || "";
        if (rol !== "admin") {
            fail("Acceso restringido: se requiere rol administrador.");
            return;
        }

        // Marcar sesión del backoffice: sso_started + sso_role
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        try {
            await fetch(`{{ url('/session/start') }}`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf
                },
                body: JSON.stringify({ role: rol })
            });
        } catch (e) {
            fail("No se pudo iniciar la sesión del backoffice.");
            return;
        }

        // Limpiar el token de la URL
        try { history.replaceState({}, document.title, "{{ route('sso') }}"); } catch (e) {}

        // Redirigir
        window.location.assign(next || DASHBOARD_URL);
    })();
    </script>
</head>
<body>
    <p>Conectando con Backoffice…</p>
</body>
</html>
