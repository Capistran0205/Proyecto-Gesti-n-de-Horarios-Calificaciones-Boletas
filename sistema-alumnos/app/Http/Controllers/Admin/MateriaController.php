<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMateriaRequest;
use App\Http\Requests\UpdateMateriaRequest;
use App\Models\AreaDisciplinar;
use App\Models\Especialidad;
use App\Models\Materia;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Gestión de materias (HU-05). Solo el Administrador (rol 1).
 * Baja lógica: una materia de baja no se asigna a docentes ni horarios, y no se modifica hasta reactivarla.
 *
 * Mensajes para la ventana emergente genérica de la vista:
 *   session('exito') y session('error'); los errores de validación llegan en $errors.
 */
class MateriaController extends Controller
{
    private const MENSAJE_ERROR = 'No se pudo completar la operación. Inténtalo de nuevo; si el problema continúa, avisa al responsable del sistema.';

    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));
        $semestre = (string) $request->query('semestre', 'todos'); // '1'..'6' o 'todos'
        $tipo = (string) $request->query('tipo', 'todos');         // '1', '2' o 'todos'
        $estado = (string) $request->query('estado', 'todos');     // '1' activas, '0' bajas o 'todos'

        $materias = Materia::query()
            ->with(['areaDisciplinar', 'especialidad'])
            ->when($buscar !== '', fn ($consulta) => $consulta->where(fn ($q) => $q
                ->where('nombre', 'ilike', "%{$buscar}%")
                ->orWhere('clave', 'ilike', "%{$buscar}%")))
            ->when(in_array($semestre, ['1', '2', '3', '4', '5', '6'], true), fn ($consulta) => $consulta->where('semestre', (int) $semestre))
            ->when(in_array($tipo, ['1', '2'], true), fn ($consulta) => $consulta->where('tipo', (int) $tipo))
            ->when(in_array($estado, ['0', '1'], true), fn ($consulta) => $consulta->where('estado', (int) $estado))
            ->orderBy('semestre')
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('admin.materias.index', [
            'materias' => $materias,
            'buscar' => $buscar,
            'semestre' => $semestre,
            'tipo' => $tipo,
            'estado' => $estado,
            'tipos' => Materia::TIPOS,
            'estados' => Materia::ESTADOS,
        ]);
    }

    public function create()
    {
        return view('admin.materias.create', $this->catalogosParaFormulario());
    }

    public function store(StoreMateriaRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['id_especialidad'] = $datos['id_especialidad'] ?? null;
        $datos['estado'] = Materia::ESTADO_ACTIVA;

        try {
            $materia = Materia::create($datos);
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('registrar materia', $error)->withInput();
        }

        return redirect()
            ->route('admin.materias.index')
            ->with('exito', "La materia {$materia->nombre} ({$materia->clave}) se registró correctamente.");
    }

    public function show(Materia $materia)
    {
        return view('admin.materias.show', ['materia' => $materia->load(['areaDisciplinar', 'especialidad'])]);
    }

    public function edit(Materia $materia)
    {
        if ($materia->estado === Materia::ESTADO_BAJA) {
            return $this->materiaDeBaja();
        }

        return view('admin.materias.edit', ['materia' => $materia] + $this->catalogosParaFormulario());
    }

    public function update(UpdateMateriaRequest $request, Materia $materia): RedirectResponse
    {
        if ($materia->estado === Materia::ESTADO_BAJA) {
            return $this->materiaDeBaja();
        }

        $datos = $request->validated();
        $datos['id_especialidad'] = $datos['id_especialidad'] ?? null;

        try {
            $materia->update($datos);
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('actualizar materia', $error)->withInput();
        }

        return redirect()
            ->route('admin.materias.index')
            ->with('exito', "La materia {$materia->nombre} ({$materia->clave}) se actualizó correctamente.");
    }

    // Baja lógica: nunca se borra el registro y las asignaciones existentes no cambian
    public function baja(Materia $materia): RedirectResponse
    {
        try {
            $materia->update(['estado' => Materia::ESTADO_BAJA]);
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('dar de baja materia', $error);
        }

        return redirect()
            ->route('admin.materias.index')
            ->with('exito', "La materia {$materia->nombre} ({$materia->clave}) fue dada de baja.");
    }

    public function reactivar(Materia $materia): RedirectResponse
    {
        try {
            $materia->update(['estado' => Materia::ESTADO_ACTIVA]);
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('reactivar materia', $error);
        }

        return redirect()
            ->route('admin.materias.index')
            ->with('exito', "La materia {$materia->nombre} ({$materia->clave}) fue reactivada.");
    }

    // Listas para los selectores del formulario
    private function catalogosParaFormulario(): array
    {
        return [
            'areas' => AreaDisciplinar::orderBy('descripcion')->get(),
            'especialidades' => Especialidad::orderBy('descripcion')->get(),
            'tipos' => Materia::TIPOS,
        ];
    }

    private function materiaDeBaja(): RedirectResponse
    {
        return redirect()
            ->route('admin.materias.index')
            ->with('error', 'La materia está de baja. Reactívala para poder modificarla.');
    }

    // Registra el detalle técnico y devuelve un mensaje genérico
    private function falloDeBaseDeDatos(string $accion, QueryException $error): RedirectResponse
    {
        Log::error("Error al {$accion}", ['detalle' => $error->errorInfo[2] ?? $error->getCode()]);

        return back()->with('error', self::MENSAJE_ERROR);
    }
}
