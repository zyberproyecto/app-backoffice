<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login'    => ['required','string','max:191'], 
            'password' => ['required','string','max:191'],
        ], [
            'login.required' => 'Ingresá tu CI o email.',
        ]);

        $login = trim($data['login']);
        $pass  = $data['password'];
        $isEmail = (bool) filter_var($login, FILTER_VALIDATE_EMAIL);

        if ($isEmail) {
            $admin = Admin::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($login, 'UTF-8')])
                ->first();
        } else {
            $ci = preg_replace('/\D/', '', $login);
            $admin = Admin::query()
                ->where('ci_usuario', $ci)
                ->first();
        }

        if (!$admin) {
            return back()
                ->withInput()
                ->withErrors(['login' => 'Usuario no encontrado.'])
                ->with('error', 'Usuario no encontrado.');
        }

        if (!Hash::check($pass, $admin->password)) {
            return back()
                ->withInput()
                ->withErrors(['login' => 'Contraseña incorrecta.'])
                ->with('error', 'Credenciales inválidas.');
        }

        if (($admin->estado ?? 'activo') !== 'activo') {
            return back()
                ->withInput()
                ->withErrors(['login' => 'Usuario inactivo.'])
                ->with('error', 'Usuario inactivo.');
        }

        Auth::guard('admin')->login($admin, false);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}