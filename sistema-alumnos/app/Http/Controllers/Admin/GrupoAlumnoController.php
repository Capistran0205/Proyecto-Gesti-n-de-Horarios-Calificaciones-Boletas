<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AsignarAlumnosRequest;
use App\Models\Grupo;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Asignación de alumnos a un grupo (HU-06). Solo el Administrador (rol 1).
 * Solo se asignan alumnos activos, del mismo semestre (y especialidad en 5.º y 6.º) y sin grupo en el periodo.
 * Un alumno solo está en un grupo por periodo y nunca se rebasa capacidad_max. Quitar a un alumno borra
 * solo su asignación (fila de detalle_grupo), no al usuario. Solo en el periodo vigente o en uno futuro.
 *
 * Mensajes para la ventana emergente genérica de la vista:
 *   session('exito') y session('error'); los errores de validación (incluidos cupo y elegibilidad,
 *   bajo la clave 'alumnos') llegan en $errors.
 */
class GrupoAlumnoController extends Controller
{
    private const MENSAJE_ERROR = 'No se pudo completar la operación. Inténtalo de nuevo; si el problema continúa, avisa al responsable del sistema.';

    public function create(Request $request, Grupo $grupo)
    {
        $grupo->load(['periodo', 'especialidad'])->loadCount('alumnos');

        if (! $grupo->permiteAsignarAlumnos()) {
            return $this->periodoCerrado($grupo);
        }

        $buscar = trim((string) $request->query('buscar'));

        $alumnos = $grupo->alumnosElegibles()
            ->when($buscar !== '', function ($consulta) use ($buscar) {
                // Busca por matrícula o por el nombre completo
                $consulta->where(fn ($q) => $q
                    ->where('usuario', 'ilike', "%{$buscar}%")
                    ->orWhereRaw("concat_ws(' ', nombre, apellido_pat, apellido_mat) ilike ?", ["%{$buscar}%"]));
            })
            ->get();

        return view('admin.grupos.alumnos.create', [
            'grupo' => $grupo,
            'alumnos' => $alumnos,
            'buscar' => $buscar,
        ]);
    }

    // Todo o nada: si algo falla no se asigna a ningún alumno
    public function store(AsignarAlumnosRequest $request, Grupo $grupo): RedirectResponse
    {
        $idsSolicitados = array_map('intval', $request->validated()['alumnos']);
        sort($idsSolicitados); // mismo orden de bloqueo en todas las capturas

        $grupo->load('periodo');

        if (! $grupo->permiteAsignarAlumnos()) {
            return $this->periodoCerrado($grupo);
        }

        try {
            DB::transaction(function () use ($grupo, $idsSolicitados) {
                // Bloquea el grupo: dos capturas simultáneas se turnan y la segunda ve el cupo ya ocupado
                $bloqueado = Grupo::whereKey($grupo->id)->lockForUpdate()->firstOrFail();

                // Bloquea a los alumnos: el mismo alumno no puede entrar a dos grupos del periodo a la vez
                DB::table('alumno')->whereIn('id_alumno', $idsSolicitados)->orderBy('id_alumno')->lockForUpdate()->get();

                $elegibles = $bloqueado->alumnosElegibles()->whereIn('id', $idsSolicitados)->pluck('id')->all();
                $noElegibles = array_values(array_diff($idsSolicitados, $elegibles));

                if (count($noElegibles) > 0) {
                    $matriculas = User::whereIn('id', $noElegibles)->orderBy('usuario')->pluck('usuario');

                    throw ValidationException::withMessages([
                        'alumnos' => $matriculas->map(fn ($matricula) => "La matrícula {$matricula} ya no es elegible para este grupo (está de baja, es de otro semestre o especialidad, o ya tiene grupo en el periodo).")->all()
                            ?: ['Alguno de los alumnos seleccionados ya no es elegible para este grupo.'],
                    ]);
                }

                $inscritos = DB::table('detalle_grupo')->where('id_grupo', $bloqueado->id)->count();
                $lugares = max(0, $bloqueado->capacidad_max - $inscritos);

                if (count($idsSolicitados) > $lugares) {
                    throw ValidationException::withMessages([
                        'alumnos' => ["Lugares disponibles ahora: {$lugares}", 'Alumnos seleccionados: ' . count($idsSolicitados)],
                    ]);
                }

                DB::table('detalle_grupo')->insert(array_map(
                    fn ($idAlumno) => ['id_grupo' => $bloqueado->id, 'id_alumno' => $idAlumno],
                    $idsSolicitados
                ));
            });
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('asignar alumnos a grupo', $error)->withInput();
        }

        $total = count($idsSolicitados);

        return redirect()
            ->route('admin.grupos.show', $grupo)
            ->with('exito', $total === 1
                ? "Se asignó 1 alumno al grupo {$grupo->nombre_grupo}."
                : "Se asignaron {$total} alumnos al grupo {$grupo->nombre_grupo}.");
    }

    // {alumno} es el id del usuario alumno (= alumno.id_alumno)
    public function destroy(Grupo $grupo, int $alumno): RedirectResponse
    {
        $grupo->load('periodo');

        if (! $grupo->permiteAsignarAlumnos()) {
            return $this->periodoCerrado($grupo);
        }

        $usuario = User::alumnos()->find($alumno);

        if (! $usuario) {
            abort(404);
        }

        try {
            $quitados = DB::table('detalle_grupo')
                ->where('id_grupo', $grupo->id)
                ->where('id_alumno', $usuario->id)
                ->delete();
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('quitar alumno de grupo', $error);
        }

        if ($quitados === 0) {
            return redirect()
                ->route('admin.grupos.show', $grupo)
                ->with('error', "El alumno {$usuario->nombre_completo} ya no pertenece al grupo {$grupo->nombre_grupo}.");
        }

        return redirect()
            ->route('admin.grupos.show', $grupo)
            ->with('exito', "El alumno {$usuario->nombre_completo} fue quitado del grupo {$grupo->nombre_grupo}. Su cuenta no se modificó.");
    }

    private function periodoCerrado(Grupo $grupo): RedirectResponse
    {
        return redirect()
            ->route('admin.grupos.show', $grupo)
            ->with('error', "El periodo {$grupo->periodo->nombre} ya terminó: no se pueden asignar ni quitar alumnos de este grupo.");
    }

    // Registra el detalle técnico y devuelve un mensaje genérico
    private function falloDeBaseDeDatos(string $accion, QueryException $error): RedirectResponse
    {
        Log::error("Error al {$accion}", ['detalle' => $error->errorInfo[2] ?? $error->getCode()]);

        return back()->with('error', self::MENSAJE_ERROR);
    }
}