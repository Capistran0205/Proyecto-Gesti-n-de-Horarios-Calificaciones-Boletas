<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

// Cambio obligatorio de la contraseña temporal (primer inicio de sesión)
class CambioContrasenaController extends Controller
{
    public function mostrar(Request $request)
    {
        if (! $request->user()->cambio_contrasena_ini) {
            return redirect()->route('panel');
        }

        return view('auth.cambiar-contrasena');
    }

    public function actualizar(Request $request)
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'contrasena' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'contrasena.required' => 'Escribe tu nueva contraseña.',
            'contrasena.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'contrasena.confirmed' => 'Las contraseñas no coinciden. Vuelve a escribirlas.',
        ]);

        if (Hash::check($datos['contrasena'], $usuario->contrasena)) {
            return back()->withErrors(['contrasena' => 'La nueva contraseña debe ser distinta de la contraseña temporal.']);
        }

        $usuario->update([
            'contrasena' => $datos['contrasena'], // el cast 'hashed' la guarda cifrada
            'cambio_contrasena_ini' => false,
        ]);

        $request->session()->regenerate();

        return redirect()->route('panel')->with('exito', 'Tu contraseña se actualizó correctamente.');
    }
}
