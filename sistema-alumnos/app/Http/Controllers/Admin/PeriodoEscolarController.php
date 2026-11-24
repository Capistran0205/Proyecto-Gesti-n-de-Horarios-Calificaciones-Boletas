<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePeriodoRequest;
use App\Http\Requests\UpdatePeriodoRequest;
use App\Models\PeriodoEscolar;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Gestión de periodos escolares semestrales. Solo el Administrador (rol 1).
 * Los periodos no se eliminan: únicamente se registran y se editan.
 *
 * Mensajes para la ventana emergente genérica de la vista:
 *   session('exito') y session('error'); los errores de validación llegan en $errors.
 */
class PeriodoEscolarController extends Controller
{
    private const MENSAJE_ERROR = 'No se pudo completar la operación. Inténtalo de nuevo; si el problema continúa, avisa al responsable del sistema.';

    public function index()
    {
        $periodos = PeriodoEscolar::orderByDesc('fecha_inicio')->paginate(15);

        return view('admin.periodos.index', [
            'periodos' => $periodos,
            'periodoVigente' => PeriodoEscolar::vigente(), // la vista lo marca en el listado
        ]);
    }

    public function create()
    {
        return view('admin.periodos.create');
    }

    public function store(StorePeriodoRequest $request): RedirectResponse
    {
        try {
            $periodo = PeriodoEscolar::create($request->validated());
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('registrar periodo escolar', $error)->withInput();
        }

        return redirect()
            ->route('admin.periodos.index')
            ->with('exito', "El periodo {$periodo->nombre} se registró correctamente.");
    }

    public function edit(PeriodoEscolar $periodo)
    {
        return view('admin.periodos.edit', ['periodo' => $periodo]);
    }

    public function update(UpdatePeriodoRequest $request, PeriodoEscolar $periodo): RedirectResponse
    {
        try {
            $periodo->update($request->validated());
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('actualizar periodo escolar', $error)->withInput();
        }

        return redirect()
            ->route('admin.periodos.index')
            ->with('exito', "El periodo {$periodo->nombre} se actualizó correctamente.");
    }

    // Registra el detalle técnico y devuelve un mensaje genérico
    private function falloDeBaseDeDatos(string $accion, QueryException $error): RedirectResponse
    {
        Log::error("Error al {$accion}", ['detalle' => $error->errorInfo[2] ?? $error->getCode()]);

        return back()->with('error', self::MENSAJE_ERROR);
    }
}
