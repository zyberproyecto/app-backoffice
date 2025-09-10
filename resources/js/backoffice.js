(() => {
  // ---------- Helpers DOM ----------
  const $  = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

  // ---------- CSRF helpers ----------
  const getMetaCsrf = () => {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  };

  const getCookie = (name) => {
    const m = document.cookie.match(new RegExp('(?:^|;\\s*)' + name + '=([^;]+)'));
    return m ? decodeURIComponent(m[1]) : '';
  };

  // Laravel suele poner el token también en la cookie XSRF-TOKEN
  const getCsrf = () => getMetaCsrf() || getCookie('XSRF-TOKEN');

  // ---------- HTTP wrapper con fetch ----------
  const http = async (url, { method = 'GET', data = null, headers = {}, ...rest } = {}) => {
    const opts = {
      method,
      headers: { 'Accept': 'application/json', ...headers },
      credentials: 'same-origin',
      ...rest
    };

    if (data && method !== 'GET') {
      if (data instanceof FormData) {
        opts.body = data; // no tocar Content-Type
      } else {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(data);
      }
      const token = getCsrf();
      if (token) opts.headers['X-CSRF-TOKEN'] = token;
    }

    const res   = await fetch(url, opts);
    const ctype = res.headers.get('content-type') || '';
    const body  = ctype.includes('application/json')
      ? await res.json().catch(() => null)
      : await res.text();

    if (!res.ok) {
      const msg = (body && body.message) ? body.message : `${res.status} ${res.statusText}`;
      throw new Error(msg);
    }
    return body;
  };

  // ---------- Enlaces de acción (si existen en la página) ----------
  const bindActions = () => {
    // Solicitudes
    $$('[data-action="aprobar-solicitud"]').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        const id = btn.dataset.id;
        if (!id) return;
        btn.disabled = true;
        try {
          await http(`/solicitudes/${id}/aprobar`, { method: 'PUT' });
          location.reload();
        } catch (err) {
          alert('Error al aprobar solicitud: ' + err.message);
        } finally { btn.disabled = false; }
      });
    });

    $$('[data-action="rechazar-solicitud"]').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        const id = btn.dataset.id;
        if (!id) return;
        btn.disabled = true;
        try {
          await http(`/solicitudes/${id}/rechazar`, { method: 'PUT' });
          location.reload();
        } catch (err) {
          alert('Error al rechazar solicitud: ' + err.message);
        } finally { btn.disabled = false; }
      });
    });

    // Comprobantes
    $$('[data-action="validar-comprobante"]').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        const id = btn.dataset.id;
        if (!id) return;
        btn.disabled = true;
        try {
          await http(`/comprobantes/${id}/validar`, { method: 'PUT' });
          location.reload();
        } catch (err) {
          alert('Error al validar comprobante: ' + err.message);
        } finally { btn.disabled = false; }
      });
    });

    $$('[data-action="rechazar-comprobante"]').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        const id = btn.dataset.id;
        if (!id) return;
        btn.disabled = true;
        try {
          await http(`/comprobantes/${id}/rechazar`, { method: 'PUT' });
          location.reload();
        } catch (err) {
          alert('Error al rechazar comprobante: ' + err.message);
        } finally { btn.disabled = false; }
      });
    });
  };

  // ---------- Init ----------
  document.addEventListener('DOMContentLoaded', () => {
    console.log('Backoffice listo 🎯');
    bindActions();
  });
})();