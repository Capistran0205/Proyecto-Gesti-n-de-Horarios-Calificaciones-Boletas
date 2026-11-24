<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocenteRequest;
use App\Http\Requests\UpdateDocenteRequest;
use App\Models\AreaDisciplinar;
use App\Models\Docente;
use App\Models\DocenteEstudio;
use App\Models\PeriodoEscolar;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Gestión de docentes (HU-03). Solo el Administrador (rol 1).
 * Las materias que puede impartir cada docente (docente_materia) se cargan directamente en la base:
 * este controlador nunca las modifica. La disponibilidad se captura en DisponibilidadDocenteController.
 *
 * Mensajes para la ventana emergente genérica de la vista:
 *   session('exito') y session('error'); los errores de validación llegan en $errors.
 * Baja con clases en el periodo vigente: session('advertencia_baja') = ['id', 'usuario', 'curp', 'nombre', 'mensaje'];
 * la vista pide confirmación y vuelve a enviar la baja con confirmar=1.
 */
class DocenteController extends Controller
{
    private const MENSAJE_ERROR = 'No se pudo completar la operación. Inténtalo de nuevo; si el problema continúa, avisa al responsable del sistema.';

    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));
        $area = (string) $request->query('area', 'todos');     // id del área disciplinar o 'todos'
        $estado = (string) $request->query('estado', 'todos'); // '1' activos, '0' bajas o 'todos'

        $docentes = User::docentes()
            ->with('docente.areaDisciplinar')
            ->when($buscar !== '', function ($consulta) use ($buscar) {
                // Busca por número de empleado o por el nombre completo ("Ana López" también funciona)
                $consulta->where(fn ($q) => $q
                    ->where('usuario', 'ilike', "%{$buscar}%")
                    ->orWhereRaw("concat_ws(' ', nombre, apellido_pat, apellido_mat) ilike ?", ["%{$buscar}%"]));
            })
            ->when(ctype_digit($area), fn ($consulta) => $consulta->whereHas('docente', fn ($q) => $q->where('id_area_disciplinar', (int) $area)))
            ->when(in_array($estado, ['0', '1'], true), fn ($consulta) => $consulta->where('estado', (int) $estado))
            ->orderBy('apellido_pat')
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('admin.docentes.index', [
            'docentes' => $docentes,
            'buscar' => $buscar,
            'area' => $area,
            'estado' => $estado,
            'areas' => AreaDisciplinar::orderBy('descripcion')->get(),
            'contrasenaEntregada' => $this->leerContrasenaEntregada(),
        ]);
    }

    public function create()
    {
        return view('admin.docentes.create', [
            'contrasenaTemporal' => User::generarContrasenaTemporal(),
        ] + $this->catalogosParaFormulario());
    }

    // Botón "Generar otra"
    public function contrasena(): JsonResponse
    {
        return response()->json(['contrasena' => User::generarContrasenaTemporal()]);
    }

    public function store(StoreDocenteRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $disponibilidad = $request->disponibilidadNormalizada();

        try {
            $usuario = DB::transaction(function () use ($datos, $disponibilidad) {
                $usuario = User::create([
                    'nombre' => $datos['nombre'],
                    'apellido_pat' => $datos['apellido_pat'],
                    'apellido_mat' => $datos['apellido_mat'] ?? null,
                    'rol' => User::ROL_DOCENTE,
                    'usuario' => $datos['usuario'],
                    'correo_electronico' => $datos['correo_electronico'],
                    'contrasena' => $datos['contrasena'], // el cast 'hashed' la guarda cifrada
                    'estado' => User::ESTADO_ACTIVO,
                    'cambio_contrasena_ini' => true,
                ]);

                $docente = Docente::create([
                    'id_docente' => $usuario->id,
                    'id_area_disciplinar' => $datos['id_area_disciplinar'],
                    'curp' => $datos['curp'],
                    'rfc' => $datos['rfc'],
                    'horas_plaza' => $datos['horas_plaza'],
                    'disponibilidad' => $disponibilidad, // bloques por día, definidos desde el formulario
                ]);

                $docente->estudios()->createMany($datos['estudios']);

                return $usuario;
            });
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('registrar docente', $error)->withInput($request->except('contrasena'));
        }

        // La contraseña viaja cifrada en la sesión solo para mostrarla una vez en el modal de éxito
        return redirect()
            ->route('admin.docentes.index')
            ->with('exito', "Docente {$usuario->nombre_completo} registrado con el número de empleado {$usuario->usuario}.")
            ->with('contrasena_entregada', Crypt::encryptString($datos['contrasena']));
    }

    public function show(User $docente)
    {
        $this->validarDocente($docente);

        $docente->load(['docente.areaDisciplinar', 'docente.estudios', 'docente.materias']);

        return view('admin.docentes.show', ['docente' => $docente]);
    }

    public function edit(User $docente)
    {
        $this->validarDocente($docente);

        if ($docente->estado === User::ESTADO_BAJA) {
            return $this->docenteDeBaja();
        }

        $docente->load(['docente.estudios']);

        return view('admin.docentes.edit', [
            'docente' => $docente,
            'resumenDisponibilidad' => $docente->docente->resumenDisponibilidad(),
            // Con clases en el periodo vigente la disponibilidad no se puede modificar
            'disponibilidadBloqueada' => $docente->docente->tieneClasesEnPeriodo(PeriodoEscolar::vigente()?->id),
        ] + $this->catalogosParaFormulario());
    }

    public function update(UpdateDocenteRequest $request, User $docente): RedirectResponse
    {
        $this->validarDocente($docente);

        if ($docente->estado === User::ESTADO_BAJA) {
            return $this->docenteDeBaja();
        }

        $datos = $request->validated();

        try {
            DB::transaction(function () use ($docente, $datos) {
                $docente->update([
                    'nombre' => $datos['nombre'],
                    'apellido_pat' => $datos['apellido_pat'],
                    'apellido_mat' => $datos['apellido_mat'] ?? null,
                    'correo_electronico' => $datos['correo_electronico'],
                ]);

                $docente->docente->update([
                    'id_area_disciplinar' => $datos['id_area_disciplinar'],
                    'curp' => $datos['curp'],
                    'rfc' => $datos['rfc'],
                    'horas_plaza' => $datos['horas_plaza'],
                ]);

                // Estudios: se borran y se vuelven a insertar (aceptable a este nivel)
                $docente->docente->estudios()->delete();
                $docente->docente->estudios()->createMany($datos['estudios']);
            });
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('actualizar docente', $error)->withInput();
        }

        return redirect()
            ->route('admin.docentes.index')
            ->with('exito', "Los datos de {$docente->nombre_completo} se actualizaron correctamente.");
    }

    // Baja lógica: nunca se borra el registro. Con clases en el periodo vigente solo se advierte y se pide confirmar.
    public function baja(Request $request, User $docente): RedirectResponse
    {
        $this->validarDocente($docente);

        $periodoVigente = PeriodoEscolar::vigente();

        if (! $request->boolean('confirmar') && $docente->docente->tieneClasesEnPeriodo($periodoVigente?->id)) {
            return back()->with('advertencia_baja', [
                'id' => $docente->id,
                'usuario' => $docente->usuario,
                'curp' => $docente->docente->curp,
                'nombre' => $docente->nombre_completo,
                'mensaje' => "El docente tiene clases en el horario del periodo vigente ({$periodoVigente->nombre}). "
                    . 'Si lo das de baja, esas clases no cambian, pero ya no podrá asignársele más.',
            ]);
        }

        try {
            DB::transaction(function () use ($docente) {
                $docente->update(['estado' => User::ESTADO_BAJA]);
                // Cierra las sesiones abiertas para que no siga usando el sistema
                DB::table('sessions')->where('user_id', $docente->id)->delete();
            });
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('dar de baja docente', $error);
        }

        return redirect()
            ->route('admin.docentes.index')
            ->with('exito', "El docente {$docente->nombre_completo} fue dado de baja.");
    }

    public function reactivar(User $docente): RedirectResponse
    {
        $this->validarDocente($docente);

        try {
            $docente->update(['estado' => User::ESTADO_ACTIVO]);
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('reactivar docente', $error);
        }

        return redirect()
            ->route('admin.docentes.index')
            ->with('exito', "El docente {$docente->nombre_completo} fue reactivado.");
    }

    // El {docente} de la ruta debe ser un usuario docente con su registro en la tabla hija
    private function validarDocente(User $usuario): void
    {
        if ((int) $usuario->rol !== User::ROL_DOCENTE || ! $usuario->docente) {
            abort(404);
        }
    }

    // Listas para los selectores del formulario
    private function catalogosParaFormulario(): array
    {
        return [
            'areas' => AreaDisciplinar::orderBy('descripcion')->get(),
            'grados' => DocenteEstudio::GRADOS,
        ];
    }

    private function docenteDeBaja(): RedirectResponse
    {
        return redirect()
            ->route('admin.docentes.index')
            ->with('error', 'El docente está de baja. Reactívalo para poder modificarlo.');
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
