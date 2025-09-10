(function() {
  // Helper: obtener el token del almacenamiento local
  function getToken() {
    return localStorage.getItem("zyber_token");
  }

  // Si no hay token, redirigir a /sso (para que vuelva a loguearse desde la landing)
  const token = getToken();
  if (!token) {
    console.warn("No hay token en localStorage. Redirigiendo a /sso...");
    window.location.href = "/sso";
    return; // corta ejecución
  }

  // Interceptar fetch para agregar siempre el Bearer token
  const originalFetch = window.fetch;
  window.fetch = function(input, init = {}) {
    init.headers = init.headers || {};
    if (getToken()) {
      init.headers["Authorization"] = "Bearer " + getToken();
    }
    return originalFetch(input, init);
  };

  // Hacer disponible globalmente el token si se necesita
  window.getZyberToken = getToken;

  console.log("boot.js cargado, token listo para usar en fetch()");
})();