<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReactivarAlumnoRequest;
use App\Http\Requests\StoreAlumnoRequest;
use App\Http\Requests\UpdateAlumnoRequest;
use App\Models\Alumno;
use App\Models\Especialidad;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Gestión de alumnos (HU-04). Solo el Administrador (rol 1).
 * La matrícula va en usuario.usuario y no se edita. La baja es lógica (se conserva el histórico) y saca al
 * alumno de su grupo del periodo vigente; al reactivar, el administrador captura el semestre
 * (y la especialidad si queda en 5.º o 6.º) y el alumno queda sin grupo.
 * Con grupo en el periodo vigente, el semestre y la especialidad no se pueden cambiar.
 *
 * Mensajes para la ventana emergente genérica de la vista:
 *   session('exito') y session('error'); los errores de validación llegan en $errors.
 */
class AlumnoController extends Controller
{
    private const MENSAJE_ERROR = 'No se pudo completar la operación. Inténtalo de nuevo; si el problema continúa, avisa al responsable del sistema.';

    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));
        $semestre = (string) $request->query('semestre', 'todos'); // '1'..'6' o 'todos'
        $grupo = (string) $request->query('grupo', 'todos');       // id de un grupo del periodo vigente o 'todos'
        $estado = (string) $request->query('estado', 'todos');     // '1' activos, '0' bajas o 'todos'

        $alumnos = User::alumnos()
            ->with('alumno.especialidad')
            ->when($buscar !== '', function ($consulta) use ($buscar) {
                // Busca por matrícula o por el nombre completo ("Ana López" también funciona)
                $consulta->where(fn ($q) => $q
                    ->where('usuario', 'ilike', "%{$buscar}%")
                    ->orWhereRaw("concat_ws(' ', nombre, apellido_pat, apellido_mat) ilike ?", ["%{$buscar}%"]));
            })
            ->when(in_array($semestre, ['1', '2', '3', '4', '5', '6'], true), fn ($consulta) => $consulta->whereHas('alumno', fn ($q) => $q->where('semestre', (int) $semestre)))
            ->when(ctype_digit($grupo), fn ($consulta) => $consulta->whereIn('id', fn ($q) => $q->select('id_alumno')->from('detalle_grupo')->where('id_grupo', (int) $grupo)))
            ->when(in_array($estado, ['0', '1'], true), fn ($consulta) => $consulta->where('estado', (int) $estado))
            ->orderBy('apellido_pat')
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('admin.alumnos.index', [
            'alumnos' => $alumnos,
            'buscar' => $buscar,
            'semestre' => $semestre,
            'grupo' => $grupo,
            'estado' => $estado,
            'grupos' => Alumno::gruposDelPeriodoVigente(),
            // Grupo vigente de los alumnos de la página: [id_alumno => {id, nombre}]
            'gruposDeAlumnos' => Alumno::gruposVigentesDe($alumnos->pluck('id')->all()),
            'especialidades' => Especialidad::orderBy('descripcion')->get(), // para la ventana de reactivación
            'contrasenaEntregada' => $this->leerContrasenaEntregada(),
        ]);
    }

    public function create()
    {
        return view('admin.alumnos.create', [
            'contrasenaTemporal' => User::generarContrasenaTemporal(),
        ] + $this->catalogosParaFormulario());
    }

    // Botón "Generar otra"
    public function contrasena(): JsonResponse
    {
        return response()->json(['contrasena' => User::generarContrasenaTemporal()]);
    }

    public function store(StoreAlumnoRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        try {
            $usuario = DB::transaction(function () use ($datos) {
                $usuario = User::create([
                    'nombre' => $datos['nombre'],
                    'apellido_pat' => $datos['apellido_pat'],
                    'apellido_mat' => $datos['apellido_mat'] ?? null,
                    'rol' => User::ROL_ALUMNO,
                    'usuario' => $datos['usuario'],
                    'correo_electronico' => $datos['correo_electronico'],
                    'contrasena' => $datos['contrasena'], // el cast 'hashed' la guarda cifrada
                    'estado' => User::ESTADO_ACTIVO,
                    'cambio_contrasena_ini' => true,
                ]);

                Alumno::create([
                    'id_alumno' => $usuario->id,
                    'id_especialidad' => $datos['id_especialidad'] ?? null,
                    'curp' => $datos['curp'],
                    'nss' => $datos['nss'] ?? null,
                    'anio_ingreso' => $datos['anio_ingreso'],
                    'semestre' => $datos['semestre'],
                ]);

                return $usuario;
            });
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('registrar alumno', $error)->withInput($request->except('contrasena'));
        }

        // La contraseña viaja cifrada en la sesión solo para mostrarla una vez en el modal de éxito
        return redirect()
            ->route('admin.alumnos.index')
            ->with('exito', "Alumno {$usuario->nombre_completo} registrado con la matrícula {$usuario->usuario}.")
            ->with('contrasena_entregada', Crypt::encryptString($datos['contrasena']));
    }

    public function show(User $alumno)
    {
        $this->validarAlumno($alumno);

        $alumno->load('alumno.especialidad');

        return view('admin.alumnos.show', [
            'alumno' => $alumno,
            'grupoVigente' => $alumno->alumno->grupoVigente(),
        ]);
    }

    public function edit(User $alumno)
    {
        $this->validarAlumno($alumno);

        if ($alumno->estado === User::ESTADO_BAJA) {
            return $this->alumnoDeBaja();
        }

        $alumno->load('alumno');

        return view('admin.alumnos.edit', [
            'alumno' => $alumno,
            // Con grupo en el periodo vigente, el semestre y la especialidad no se pueden cambiar
            'grupoVigente' => $alumno->alumno->grupoVigente(),
        ] + $this->catalogosParaFormulario());
    }

    public function update(UpdateAlumnoRequest $request, User $alumno): RedirectResponse
    {
        $this->validarAlumno($alumno);

        if ($alumno->estado === User::ESTADO_BAJA) {
            return $this->alumnoDeBaja();
        }

        $datos = $request->validated();

        try {
            DB::transaction(function () use ($alumno, $datos) {
                $alumno->update([
                    'nombre' => $datos['nombre'],
                    'apellido_pat' => $datos['apellido_pat'],
                    'apellido_mat' => $datos['apellido_mat'] ?? null,
                    'correo_electronico' => $datos['correo_electronico'],
                ]);

                $alumno->alumno->update([
                    'id_especialidad' => $datos['id_especialidad'] ?? null,
                    'curp' => $datos['curp'],
                    'nss' => $datos['nss'] ?? null,
                    'anio_ingreso' => $datos['anio_ingreso'],
                    'semestre' => $datos['semestre'],
                ]);
            });
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('actualizar alumno', $error)->withInput();
        }

        return redirect()
            ->route('admin.alumnos.index')
            ->with('exito', "Los datos de {$alumno->nombre_completo} se actualizaron correctamente.");
    }

    // Baja lógica: nunca se borra el registro. Sale del grupo del periodo vigente (libera el cupo);
    // sus periodos anteriores y sus calificaciones se conservan.
    public function baja(User $alumno): RedirectResponse
    {
        $this->validarAlumno($alumno);

        try {
            $salioDeGrupo = DB::transaction(function () use ($alumno) {
                $alumno->update(['estado' => User::ESTADO_BAJA]);
                // Cierra las sesiones abiertas para que no siga usando el sistema
                DB::table('sessions')->where('user_id', $alumno->id)->delete();

                return $alumno->alumno->salirDelGrupoVigente() > 0;
            });
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('dar de baja alumno', $error);
        }

        return redirect()
            ->route('admin.alumnos.index')
            ->with('exito', "El alumno {$alumno->nombre_completo} fue dado de baja" . ($salioDeGrupo ? ' y salió de su grupo.' : '.'));
    }

    // El semestre no se actualiza solo: el administrador lo captura al reactivar
    public function reactivar(ReactivarAlumnoRequest $request, User $alumno): RedirectResponse
    {
        $this->validarAlumno($alumno);

        if ($alumno->estado === User::ESTADO_ACTIVO) {
            return redirect()
                ->route('admin.alumnos.index')
                ->with('error', 'El alumno ya está activo. Para cambiar su semestre, modifícalo desde la edición.');
        }

        $datos = $request->validated();

        try {
            DB::transaction(function () use ($alumno, $datos) {
                $alumno->alumno->update([
                    'semestre' => $datos['semestre'],
                    'id_especialidad' => $datos['id_especialidad'] ?? null,
                ]);
                $alumno->update(['estado' => User::ESTADO_ACTIVO]);
            });
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('reactivar alumno', $error);
        }

        return redirect()
            ->route('admin.alumnos.index')
            ->with('exito', "El alumno {$alumno->nombre_completo} fue reactivado en {$alumno->alumno->semestre_texto}.");
    }

    // El {alumno} de la ruta debe ser un usuario alumno con su registro en la tabla hija
    private function validarAlumno(User $usuario): void
    {
        if ((int) $usuario->rol !== User::ROL_ALUMNO || ! $usuario->alumno) {
            abort(404);
        }
    }

    // Lista para el selector de especialidad del formulario
    private function catalogosParaFormulario(): array
    {
        return ['especialidades' => Especialidad::orderBy('descripcion')->get()];
    }

    private function alumnoDeBaja(): RedirectResponse
    {
        return redirect()
            ->route('admin.alumnos.index')
            ->with('error', 'El alumno está de baja. Reactívalo para poder modificarlo.');
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
