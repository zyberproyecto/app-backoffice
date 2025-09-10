<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Usuario;

class AuthController extends Controller
{
    public function showLogin()
    {
        // Tu vista está en resources/views/auth/login.blade.php
        return view('auth.login');
    }

    /**
     * Login local del backoffice validando contra la API de Usuarios.
     * Acepta CI o email en el campo "login".
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'login'    => ['required','string','max:191'],
            'password' => ['required','string','max:191'],
        ], [
            'login.required' => 'Debes ingresar tu CI o email.',
        ]);

        $apiBase = rtrim(config('services.usuarios_api.url') ?? env('USUARIOS_API_URL', ''), '/');
        if (!$apiBase) {
            return back()->withErrors(['login' => 'Falta USUARIOS_API_URL en el .env del backoffice.'])->withInput();
        }

        $login = trim($data['login']);
        $pass  = $data['password'];

        // 1) Intento moderno
        $resp = Http::acceptJson()->post("$apiBase/api/v1/login", [
            'login'    => $login,
            'password' => $pass,
        ]);

        // 2) Fallback a /api/login (algunas APIs usan email/password)
        if (!$resp->ok()) {
            $payload = filter_var($login, FILTER_VALIDATE_EMAIL)
                ? ['email' => $login, 'password' => $pass]
                : ['login' => $login, 'password' => $pass];

            $resp = Http::acceptJson()->post("$apiBase/api/login", $payload);
        }

        if (!$resp->ok()) {
            return back()->withErrors(['login' => 'Credenciales inválidas.'])->withInput();
        }

        $json  = $resp->json() ?: [];
        $token = $json['token'] ?? ($json['access_token'] ?? ($json['data']['token'] ?? null));
        if (!$token) {
            return back()->withErrors(['login' => 'La API respondió sin token.'])->withInput();
        }

        // Guardamos el token por si luego se necesita
        session(['usuarios_api_token' => $token]);

        // Traemos perfil para identificar CI/Email
        $ci = null; $email = null;
        try {
            $me = Http::withToken($token)->acceptJson()->get("$apiBase/api/v1/me");
            if (!$me->ok()) {
                $me = Http::withToken($token)->acceptJson()->get("$apiBase/api/perfil");
            }
            if ($me->ok()) {
                $p = $me->json() ?: [];
                $ci    = $p['ci_usuario'] ?? $p['ci'] ?? ($p['data']['ci_usuario'] ?? null);
                $email = $p['email'] ?? ($p['data']['email'] ?? null);
            }
        } catch (\Throwable $e) {
            // Ignoramos: usamos el login como último recurso
        }

        // Buscamos usuario local por CI o Email
        $identifier = $ci ?: ($email ?: $login);

        /** @var \App\Models\Usuario|null $local */
        $local = Usuario::query()
            ->when($identifier, function ($q) use ($identifier) {
                $q->where('ci_usuario', $identifier)
                  ->orWhere('email', $identifier);
            })
            ->first();

        if (!$local) {
            return back()->withErrors(['login' => 'Usuario no encontrado en el backoffice.'])->withInput();
        }

        // Abrimos sesión local con el ID de la tabla "usuarios"
        Auth::loginUsingId($local->id, false);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * SSO: recibe ?token=... (token de la API de Usuarios), obtiene el perfil,
     * busca el usuario local y abre sesión con su ID local.
     */
    public function sso(Request $request)
    {
        $token = $request->query('token');
        if (!$token) {
            return redirect()->route('login')->withErrors(['login' => 'Falta token en SSO.']);
        }

        session(['usuarios_api_token' => $token]);

        $apiBase = rtrim(config('services.usuarios_api.url') ?? env('USUARIOS_API_URL', ''), '/');
        if (!$apiBase) {
            return redirect()->route('login')->withErrors(['login' => 'Falta USUARIOS_API_URL en el .env del backoffice.']);
        }

        // Leemos perfil para identificar CI/Email
        $ci = null; $email = null;
        try {
            $me = Http::withToken($token)->acceptJson()->get("$apiBase/api/v1/me");
            if (!$me->ok()) {
                $me = Http::withToken($token)->acceptJson()->get("$apiBase/api/perfil");
            }
            if ($me->ok()) {
                $p = $me->json() ?: [];
                $ci    = $p['ci_usuario'] ?? $p['ci'] ?? ($p['data']['ci_usuario'] ?? null);
                $email = $p['email'] ?? ($p['data']['email'] ?? null);
            }
        } catch (\Throwable $e) {
            // seguimos igual, probamos con lo que haya
        }

        $identifier = $ci ?: $email;

        /** @var \App\Models\Usuario|null $local */
        $local = null;
        if ($identifier) {
            $local = Usuario::where('ci_usuario', $identifier)
                ->orWhere('email', $identifier)
                ->first();
        }

        if (!$local) {
            return redirect()->route('login')->withErrors(['login' => 'SSO: usuario no encontrado en el backoffice.']);
        }

        // OJO: aquí usamos el ID LOCAL, no el del perfil de la API
        Auth::loginUsingId($local->id, false);

        return redirect()->intended(route('dashboard'));
    }

    public function ssoViaGet(Request $request)
    {
        return $this->sso($request);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}