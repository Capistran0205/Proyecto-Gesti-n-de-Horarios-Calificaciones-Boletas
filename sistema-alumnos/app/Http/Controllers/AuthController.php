<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const MAX_INTENTOS = 5;
    private const BLOQUEO_SEGUNDOS = 300; // RN-15

    public function mostrarLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $identificador = Str::lower(trim((string) $request->input('identificador')));
        $contrasena = (string) $request->input('contrasena');

        if ($identificador === '' || $contrasena === '') {
            return back()
                ->withErrors(['login' => 'Ingrese su nombre de usuario y su contraseña para acceder.'])
                ->onlyInput('identificador');
        }

        $llave = 'login|' . $identificador . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($llave, self::MAX_INTENTOS)) {
            $minutos = (int) ceil(RateLimiter::availableIn($llave) / 60);

            return back()
                ->withErrors(['login' => "Demasiados intentos. Intente de nuevo en {$minutos} minuto(s)."])
                ->onlyInput('identificador');
        }

        // RN-08: se entra con usuario o con correo
        $campo = filter_var($identificador, FILTER_VALIDATE_EMAIL) ? 'correo' : 'usuario';

        // RN-13: solo usuarios activos
        $credenciales = [
            $campo => $identificador,
            'password' => $contrasena,
            'estado' => 1,
        ];

        if (Auth::attempt($credenciales)) {
            $request->session()->regenerate();
            RateLimiter::clear($llave);

            return redirect()->route('panel');
        }

        RateLimiter::hit($llave, self::BLOQUEO_SEGUNDOS);

        // RN-14: el mensaje no dice cuál dato falló
        return back()
            ->withErrors(['login' => 'Nombre de usuario o contraseña incorrectos. Por favor, inténtelo de nuevo.'])
            ->onlyInput('identificador');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}