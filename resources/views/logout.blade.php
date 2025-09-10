<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cerrando sesión…</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        (function () {
            try {
                // Borrar credenciales locales
                localStorage.removeItem("zyber_token");
                localStorage.removeItem("token");
                localStorage.removeItem("rol");
                localStorage.removeItem("token_saved_at");
            } catch (e) {
                console.warn("No se pudo limpiar todo el storage:", e);
            }

            // Redirigir a la landing/login (ajusta la URL si es necesario)
            // Ejemplos:
            // window.location.assign("/"); // raíz del backoffice
            // window.location.assign("http://127.0.0.1:5500/landing/login.html");
            window.location.assign("../landing_page/login.html");
        })();
    </script>
</head>
<body>
    <p>Finalizando sesión…</p>
</body>
</html>