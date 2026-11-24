<?php

namespace App\Http\Controllers\SuperUsuario;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdministradorRequest;
use App\Http\Requests\UpdateAdministradorRequest;
use App\Models\Administrador;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Gestión de administradores (HU-02). Solo el SuperUsuario (rol 0).
 *
 * Mensajes para la ventana emergente genérica de la vista:
 *   session('exito') y session('error'); los errores de validación llegan en $errors.
 */
class AdministradorController extends Controller
{
    private const MENSAJE_ERROR = 'No se pudo completar la operación. Inténtalo de nuevo; si el problema continúa, avisa al responsable del sistema.';

    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));
        $estado = (string) $request->query('estado', 'todos'); // '1' activos, '0' bajas, 'todos'

        $administradores = User::administradores()
            ->with('administrador')
            ->when($buscar !== '', function ($consulta) use ($buscar) {
                // Busca por número de empleado o por el nombre completo ("Ana López" también funciona)
                $consulta->where(fn ($q) => $q
                    ->where('usuario', 'ilike', "%{$buscar}%")
                    ->orWhereRaw("concat_ws(' ', nombre, apellido_pat, apellido_mat) ilike ?", ["%{$buscar}%"]));
            })
            ->when(in_array($estado, ['0', '1'], true), fn ($consulta) => $consulta->where('estado', (int) $estado))
            ->orderBy('apellido_pat')
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('superusuario.administradores.index', [
            'administradores' => $administradores,
            'buscar' => $buscar,
            'estado' => $estado,
            'contrasenaEntregada' => $this->leerContrasenaEntregada(),
        ]);
    }

    public function create()
    {
        return view('superusuario.administradores.create', [
            'contrasenaTemporal' => User::generarContrasenaTemporal(),
        ]);
    }

    // Botón "Generar otra"
    public function contrasena(): JsonResponse
    {
        return response()->json(['contrasena' => User::generarContrasenaTemporal()]);
    }

    public function store(StoreAdministradorRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        try {
            $usuario = DB::transaction(function () use ($datos) {
                $usuario = User::create([
                    'nombre' => $datos['nombre'],
                    'apellido_pat' => $datos['apellido_pat'],
                    'apellido_mat' => $datos['apellido_mat'] ?? null,
                    'rol' => User::ROL_ADMINISTRADOR,
                    'usuario' => $datos['usuario'],
                    'correo_electronico' => $datos['correo_electronico'],
                    'contrasena' => $datos['contrasena'], // el cast 'hashed' la guarda cifrada
                    'estado' => User::ESTADO_ACTIVO,
                    'cambio_contrasena_ini' => true,
                ]);

                Administrador::create([
                    'id_admin' => $usuario->id,
                    'curp' => $datos['curp'],
                    'rfc' => $datos['rfc'],
                ]);

                return $usuario;
            });
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('registrar administrador', $error)->withInput($request->except('contrasena'));
        }

        // La contraseña viaja cifrada en la sesión solo para mostrarla una vez en el modal de éxito
        return redirect()
            ->route('superusuario.administradores.index')
            ->with('exito', "Administrador {$usuario->nombre_completo} registrado con el número de empleado {$usuario->usuario}.")
            ->with('contrasena_entregada', Crypt::encryptString($datos['contrasena']));
    }

    public function show(User $usuario)
    {
        $this->validarAdministrador($usuario);

        return view('superusuario.administradores.show', ['administrador' => $usuario]);
    }

    public function edit(User $usuario)
    {
        $this->validarAdministrador($usuario);

        return view('superusuario.administradores.edit', ['administrador' => $usuario]);
    }

    public function update(UpdateAdministradorRequest $request, User $usuario): RedirectResponse
    {
        $this->validarAdministrador($usuario);
        $datos = $request->validated();

        try {
            DB::transaction(function () use ($usuario, $datos) {
                $usuario->update([
                    'nombre' => $datos['nombre'],
                    'apellido_pat' => $datos['apellido_pat'],
                    'apellido_mat' => $datos['apellido_mat'] ?? null,
                    'correo_electronico' => $datos['correo_electronico'],
                ]);

                $usuario->administrador->update([
                    'curp' => $datos['curp'],
                    'rfc' => $datos['rfc'],
                ]);
            });
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('actualizar administrador', $error)->withInput();
        }

        return redirect()
            ->route('superusuario.administradores.index')
            ->with('exito', "Los datos de {$usuario->nombre_completo} se actualizaron correctamente.");
    }

    // Baja lógica: nunca se borra el registro
    public function baja(User $usuario): RedirectResponse
    {
        $this->validarAdministrador($usuario);

        try {
            DB::transaction(function () use ($usuario) {
                $usuario->update(['estado' => User::ESTADO_BAJA]);
                // Cierra las sesiones abiertas para que no siga usando el sistema
                DB::table('sessions')->where('user_id', $usuario->id)->delete();
            });
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('dar de baja administrador', $error);
        }

        return redirect()
            ->route('superusuario.administradores.index')
            ->with('exito', "El administrador {$usuario->nombre_completo} fue dado de baja.");
    }

    public function reactivar(User $usuario): RedirectResponse
    {
        $this->validarAdministrador($usuario);

        try {
            $usuario->update(['estado' => User::ESTADO_ACTIVO]);
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('reactivar administrador', $error);
        }

        return redirect()
            ->route('superusuario.administradores.index')
            ->with('exito', "El administrador {$usuario->nombre_completo} fue reactivado.");
    }

    // El {usuario} de la ruta debe ser un administrador con su registro en la tabla hija
    private function validarAdministrador(User $usuario): void
    {
        if ((int) $usuario->rol !== User::ROL_ADMINISTRADOR || ! $usuario->administrador) {
            abort(404);
        }
    }

    // Registra el detalle técnico (sin datos sensibles) y devuelve un mensaje genérico
    private function falloDeBaseDeDatos(string $accion, QueryException $error): RedirectResponse
    {
        Log::error("Error al {$accion}", ['detalle' => $error->errorInfo[2] ?? $error->getCode()]);

        return back()->with('error', self::MENSAJE_ERROR);
    }

    private function leerContrasenaEntregada(): ?string
    {
        $cifrada = session('contrasena_entregada');

        if (! $cifrada) {
            return null;
        }

        try {
            return Crypt::decryptString($cifrada);
        } catch (\Throwable) {
            return null;
        }
    }
}
