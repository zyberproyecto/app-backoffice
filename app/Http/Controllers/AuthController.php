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
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login'    => ['required','string','max:191'], // CI o email
            'password' => ['required','string','max:191'],
        ], [
            'login.required' => 'Ingresá tu CI o email.',
        ]);

        $login = trim($data['login']);
        $pass  = $data['password'];

        // ¿Email o CI?
        $isEmail = (bool) filter_var($login, FILTER_VALIDATE_EMAIL);

        /** @var Admin|null $admin */
        if ($isEmail) {
            // Email case-insensitive (evita problemas de collation)
            $admin = Admin::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($login, 'UTF-8')])
                ->first();
        } else {
            // Normalizar CI (quita puntos/guiones/espacios)
            $ci = preg_replace('/[.\-\s]/', '', $login);
            $admin = Admin::query()
                ->where('ci_usuario', $ci)
                ->first();
        }

        if (!$admin) {
            return back()->withErrors(['login' => 'Usuario no encontrado.'])->withInput();
        }
        if (!Hash::check($pass, $admin->password)) {
            return back()->withErrors(['login' => 'Contraseña incorrecta.'])->withInput();
        }
        if (($admin->estado ?? 'activo') !== 'activo') {
            return back()->withErrors(['login' => 'Usuario inactivo.'])->withInput();
        }

        // Abrir sesión del guard admin
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