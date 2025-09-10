<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        // ✅ usar get() + (bool) en lugar de boolean()
        $ssoStarted = (bool) $request->session()->get('sso_started', false);

        if ($ssoStarted) {
            // (Opcional) si guardás el rol en sesión, validalo
            $role = $request->session()->get('sso_role');
            if ($role && $role !== 'admin') {
                abort(403, 'Solo administradores.');
            }
            return $next($request);
        }

        // Guardar a dónde quería ir, para volver después del SSO
        $request->session()->put('intended_url', $request->fullUrl());

        // Redirigir a SSO preservando token/next si vinieron
        $params = ['next' => $request->fullUrl()];
        if ($request->query('token')) {
            $params['token'] = $request->query('token');
        }
        return redirect()->route('sso', $params);
    }
}