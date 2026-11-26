<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGrupoRequest;
use App\Http\Requests\UpdateGrupoRequest;
use App\Models\Alumno;
use App\Models\Especialidad;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Gestión de grupos (HU-06). Solo el Administrador (rol 1).
 * Un grupo es periodo + semestre + letra. El administrador siempre lo crea con capacidad 40 y no puede
 * cambiarla (solo el SuperUsuario, en CapacidadGrupoController). Los grupos no se eliminan.
 * Con alumnos inscritos solo se edita la letra; el periodo nunca cambia.
 *
 * Mensajes para la ventana emergente genérica de la vista:
 *   session('exito') y session('error'); los errores de validación llegan en $errors.
 */
class GrupoController extends Controller
{
    private const MENSAJE_ERROR = 'No se pudo completar la operación. Inténtalo de nuevo; si el problema continúa, avisa al responsable del sistema.';

    public function index(Request $request)
    {
        $periodoVigente = PeriodoEscolar::vigente();
        $periodos = PeriodoEscolar::orderByDesc('fecha_inicio')->get();

        // Sin filtro se muestra el periodo vigente
        $idPeriodo = ctype_digit((string) $request->query('periodo')) ? (int) $request->query('periodo') : $periodoVigente?->id;
        $semestre = (string) $request->query('semestre', 'todos'); // '1'..'6' o 'todos'

        $grupos = Grupo::with('especialidad')
            ->withCount('alumnos')
            ->when($idPeriodo !== null, fn ($consulta) => $consulta->delPeriodo($idPeriodo), fn ($consulta) => $consulta->whereRaw('false'))
            ->when(in_array($semestre, ['1', '2', '3', '4', '5', '6'], true), fn ($consulta) => $consulta->where('semestre', (int) $semestre))
            ->ordenados()
            ->paginate(15)
            ->withQueryString();

        return view('admin.grupos.index', [
            'grupos' => $grupos,
            'periodos' => $periodos,
            'periodoVigente' => $periodoVigente,
            'idPeriodo' => $idPeriodo,
            'semestre' => $semestre,
        ]);
    }

    public function create(Request $request)
    {
        $periodoVigente = PeriodoEscolar::vigente();

        return view('admin.grupos.create', [
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(),
            'periodoVigente' => $periodoVigente,
            'idPeriodoSugerido' => ctype_digit((string) $request->query('periodo')) ? (int) $request->query('periodo') : $periodoVigente?->id,
            'capacidad' => Grupo::CAPACIDAD_INICIAL,
        ] + $this->catalogosParaFormulario());
    }

    public function store(StoreGrupoRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        try {
            $grupo = Grupo::create([
                'id_periodo' => $datos['id_periodo'],
                'semestre' => $datos['semestre'],
                'letra' => $datos['letra'],
                'id_especialidad' => $datos['id_especialidad'] ?? null,
                'capacidad_max' => Grupo::CAPACIDAD_INICIAL, // el administrador no puede subirla
            ]);
        } catch (QueryException $error) {
            // Dos altas simultáneas del mismo grupo: la restricción uq_grupo_periodo detiene la segunda
            if ($error->getCode() === '23505') {
                $periodo = PeriodoEscolar::find($datos['id_periodo']);
                $nombre = Alumno::nombreGrupo((int) $datos['semestre'], $datos['letra']);

                return back()->withInput()->withErrors(['letra' => "El grupo {$nombre} ya está registrado en el periodo {$periodo?->nombre}."]);
            }

            return $this->falloDeBaseDeDatos('registrar grupo', $error)->withInput();
        }

        return redirect()
            ->route('admin.grupos.index', ['periodo' => $grupo->id_periodo])
            ->with('exito', "El grupo {$grupo->nombre_grupo} se registró correctamente.");
    }

    public function show(Grupo $grupo)
    {
        $grupo->load(['periodo', 'especialidad'])->loadCount('alumnos');

        return view('admin.grupos.show', [
            'grupo' => $grupo,
            'alumnos' => $grupo->usuariosInscritos()->get(), // ordenados por apellido; el grupo tiene a lo más su capacidad
            'permiteAsignar' => $grupo->permiteAsignarAlumnos(),
        ]);
    }

    public function edit(Grupo $grupo)
    {
        $grupo->load(['periodo', 'especialidad'])->loadCount('alumnos');

        return view('admin.grupos.edit', [
            'grupo' => $grupo,
            // Con alumnos inscritos, el semestre y la especialidad no se pueden cambiar
            'tieneAlumnos' => $grupo->alumnos_count > 0,
        ] + $this->catalogosParaFormulario());
    }

    public function update(UpdateGrupoRequest $request, Grupo $grupo): RedirectResponse
    {
        $datos = $request->validated();

        try {
            // La capacidad no está entre los datos validados: no se puede modificar desde aquí
            $grupo->update([
                'semestre' => $datos['semestre'],
                'letra' => $datos['letra'],
                'id_especialidad' => $datos['id_especialidad'] ?? null,
            ]);
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('actualizar grupo', $error)->withInput();
        }

        return redirect()
            ->route('admin.grupos.index', ['periodo' => $grupo->id_periodo])
            ->with('exito', "El grupo {$grupo->nombre_grupo} se actualizó correctamente.");
    }

    // Lista para el selector de especialidad del formulario
    private function catalogosParaFormulario(): array
    {
        return [
            'especialidades' => Especialidad::orderBy('descripcion')->get(),
            'letras' => Grupo::letras(),
        ];
    }

    // Registra el detalle técnico y devuelve un mensaje genérico
    private function falloDeBaseDeDatos(string $accion, QueryException $error): RedirectResponse
    {
        Log::error("Error al {$accion}", ['detalle' => $error->errorInfo[2] ?? $error->getCode()]);

        return back()->with('error', self::MENSAJE_ERROR);
    }
}