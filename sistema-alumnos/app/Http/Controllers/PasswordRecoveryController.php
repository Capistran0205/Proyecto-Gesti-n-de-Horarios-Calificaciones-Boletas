<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PasswordRecoveryController extends Controller
{
    private const MAX_SOLICITUDES_POR_HORA = 3; // RN-73

    public function formulario()
    {
        return view('auth.recuperar');
    }

    public function enviar(Request $request)
    {
        $identificador = Str::lower(trim((string) $request->input('identificador')));

        if ($identificador === '') {
            return back()->withErrors(['identificador' => 'Ingresa tu número de usuario o tu correo.']);
        }

        $llave = 'recuperar|' . $identificador . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($llave, self::MAX_SOLICITUDES_POR_HORA)) {
            return back()
                ->withErrors(['identificador' => 'Hiciste demasiadas solicitudes. Intenta de nuevo más tarde.'])
                ->onlyInput('identificador');
        }
        RateLimiter::hit($llave, 3600);

        // RN usuario o correo y las bajas no recuperan
        $usuario = User::where('estado', 1)
            ->where(fn ($consulta) => $consulta
                ->where('usuario', $identificador)
                ->orWhere('correo_electronico', $identificador))
            ->first();

        if ($usuario) {
            try {
                Password::sendResetLink(['correo_electronico' => $usuario->correo_electronico]);
            } catch (\Throwable $error) {
                report($error); // si falla el correo, no se revela nada al usuario
            }
        }

        // RN: misma respuesta exista o no la cuenta
        return view('auth.estado', [
            'titulo' => 'Revisa tu correo',
            'tipo' => 'exito',
            'mensaje' => 'Si el dato existe, enviamos un mensaje de recuperación al correo registrado.',
            'textos' => [
                'Abre el enlace del mensaje para crear una nueva contraseña.',
                '¿No te llegó? Revisa tu carpeta de spam o solicita otro mensaje.',
            ],
            'botones' => [
                ['texto' => 'Reenviar mensaje', 'url' => route('password.request'), 'principal' => false],
                ['texto' => 'Volver al inicio de sesión', 'url' => route('login'), 'principal' => true],
            ],
        ]);
    }

    public function formularioRestablecer(Request $request, string $token)
    {
        $usuario = $this->buscarUsuarioActivo((string) $request->query('usuario'));

        if (! $usuario || ! Password::tokenExists($usuario, $token)) {
            return $this->enlaceNoValido();
        }

        return view('auth.restablecer', ['token' => $token, 'usuario' => $usuario->usuario]);
    }

    public function restablecer(Request $request)
    {
        $datos = $request->validate([
            'token' => ['required', 'string'],
            'usuario' => ['required', 'string'],
            'contrasena' => ['required', 'string', 'min:8', 'confirmed'], // RN-11
        ], [
            'contrasena.required' => 'Escribe tu nueva contraseña.',
            'contrasena.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'contrasena.confirmed' => 'Las contraseñas no coinciden. Vuelve a escribirlas.',
        ]);

        $usuario = $this->buscarUsuarioActivo($datos['usuario']);

        if (! $usuario) {
            return $this->enlaceNoValido();
        }

        $resultado = Password::reset(
            ['correo_electronico' => $usuario->correo_electronico, 'password' => $datos['contrasena'], 'token' => $datos['token']],
            function (User $usuario, string $contrasena) {
                $usuario->contrasena = $contrasena; // el modelo la guarda hasheada
                $usuario->cambio_contrasena_ini = false; // ya eligió su propia contraseña
                $usuario->save();

                // Cierra las sesiones abiertas con la contraseña anterior
                DB::table('sessions')->where('user_id', $usuario->id)->delete();
            }
        );

        if ($resultado !== Password::PASSWORD_RESET) {
            return $this->enlaceNoValido();
        }

        return view('auth.estado', [
            'titulo' => 'Contraseña actualizada',
            'tipo' => 'exito',
            'mensaje' => 'Tu contraseña se actualizó correctamente.',
            'textos' => ['Ya puedes iniciar sesión con tu nueva contraseña.'],
            'botones' => [
                ['texto' => 'Ir al inicio de sesión', 'url' => route('login'), 'principal' => true],
            ],
        ]);
    }

    private function buscarUsuarioActivo(string $nombreUsuario): ?User
    {
        return User::where('usuario', Str::lower(trim($nombreUsuario)))
            ->where('estado', 1)
            ->first();
    }

    private function enlaceNoValido()
    {
        return view('auth.estado', [
            'titulo' => 'Enlace no válido',
            'tipo' => 'error',
            'mensaje' => 'El enlace de recuperación venció o ya fue utilizado.',
            'botones' => [
                ['texto' => 'Solicitar un nuevo mensaje', 'url' => route('password.request'), 'principal' => true],
                ['texto' => 'Volver al inicio de sesión', 'url' => route('login'), 'principal' => false],
            ],
        ]);
    }
}