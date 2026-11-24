<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Mientras el usuario conserve la contraseña temporal solo puede cambiarla
class ForzarCambioContrasena
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && $usuario->cambio_contrasena_ini) {
            return redirect()->route('contrasena.cambiar');
        }

        return $next($request);
    }
}
