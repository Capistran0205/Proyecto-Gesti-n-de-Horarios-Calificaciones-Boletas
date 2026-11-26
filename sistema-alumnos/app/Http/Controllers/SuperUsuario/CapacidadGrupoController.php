<?php

namespace App\Http\Controllers\SuperUsuario;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCapacidadRequest;
use App\Models\Grupo;
use App\Models\PeriodoEscolar;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Capacidad máxima de un grupo (HU-06). Solo el SuperUsuario (rol 0).
 * El listado es de solo lectura: sirve para llegar a la pantalla de capacidad de cada grupo.
 * Sin límite superior de negocio (solo el de la columna SMALLINT) y nunca por debajo de los alumnos inscritos.
 *
 * Mensajes para la ventana emergente genérica de la vista:
 *   session('exito') y session('error'); los errores de validación llegan en $errors.
 */
class CapacidadGrupoController extends Controller
{
    private const MENSAJE_ERROR = 'No se pudo completar la operación. Inténtalo de nuevo; si el problema continúa, avisa al responsable del sistema.';

    public function index(Request $request)
    {
        $periodoVigente = PeriodoEscolar::vigente();

        // Sin filtro se muestra el periodo vigente (misma lógica que el listado del administrador)
        $idPeriodo = ctype_digit((string) $request->query('periodo')) ? (int) $request->query('periodo') : $periodoVigente?->id;
        $semestre = (string) $request->query('semestre', 'todos'); // '1'..'6' o 'todos'

        $grupos = Grupo::with('especialidad')
            ->withCount('alumnos')
            ->when($idPeriodo !== null, fn ($consulta) => $consulta->delPeriodo($idPeriodo), fn ($consulta) => $consulta->whereRaw('false'))
            ->when(in_array($semestre, ['1', '2', '3', '4', '5', '6'], true), fn ($consulta) => $consulta->where('semestre', (int) $semestre))
            ->ordenados()
            ->paginate(15)
            ->withQueryString();

        return view('superusuario.grupos.index', [
            'grupos' => $grupos,
            'periodos' => PeriodoEscolar::orderByDesc('fecha_inicio')->get(),
            'periodoVigente' => $periodoVigente,
            'idPeriodo' => $idPeriodo,
            'semestre' => $semestre,
        ]);
    }

    public function edit(Grupo $grupo)
    {
        $grupo->load(['periodo', 'especialidad'])->loadCount('alumnos');

        return view('superusuario.grupos.capacidad', ['grupo' => $grupo]);
    }

    public function update(UpdateCapacidadRequest $request, Grupo $grupo): RedirectResponse
    {
        $capacidad = (int) $request->validated()['capacidad_max'];

        try {
            DB::transaction(function () use ($grupo, $capacidad) {
                // Con el grupo bloqueado, nadie se inscribe mientras se revisa el mínimo
                $bloqueado = Grupo::whereKey($grupo->id)->lockForUpdate()->firstOrFail();
                $inscritos = DB::table('detalle_grupo')->where('id_grupo', $bloqueado->id)->count();

                if ($capacidad < $inscritos) {
                    throw ValidationException::withMessages([
                        'capacidad_max' => "La capacidad no puede ser menor a los alumnos ya inscritos ({$inscritos}).",
                    ]);
                }

                $bloqueado->update(['capacidad_max' => $capacidad]);
            });
        } catch (QueryException $error) {
            Log::error('Error al actualizar capacidad de grupo', ['detalle' => $error->errorInfo[2] ?? $error->getCode()]);

            return back()->withInput()->with('error', self::MENSAJE_ERROR);
        }

        return redirect()
            ->route('superusuario.grupos.index', ['periodo' => $grupo->id_periodo])
            ->with('exito', "La capacidad del grupo {$grupo->nombre_grupo} ahora es de {$capacidad} alumnos.");
    }
}