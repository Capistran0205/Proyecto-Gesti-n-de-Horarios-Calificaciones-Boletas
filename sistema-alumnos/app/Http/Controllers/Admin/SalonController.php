<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalonRequest;
use App\Http\Requests\UpdateSalonRequest;
use App\Models\Salon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Gestión de aulas (incluidas las especiales). Solo el Administrador (rol 1).
 * Las aulas no se dan de baja: únicamente se registran y se editan.
 *
 * Mensajes para la ventana emergente genérica de la vista:
 *   session('exito') y session('error'); los errores de validación llegan en $errors.
 */
class SalonController extends Controller
{
    private const MENSAJE_ERROR = 'No se pudo completar la operación. Inténtalo de nuevo; si el problema continúa, avisa al responsable del sistema.';

    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));
        $tipo = (string) $request->query('tipo', 'todos'); // '1' normal, '2' especial, 'todos'

        $aulas = Salon::query()
            ->when($buscar !== '', fn ($consulta) => $consulta->buscar($buscar))
            ->when(in_array($tipo, ['1', '2'], true), fn ($consulta) => $consulta->where('tipo', (int) $tipo))
            ->orderBy('edificio')
            ->orderBy('descripcion')
            ->paginate(15)
            ->withQueryString();

        return view('admin.aulas.index', [
            'aulas' => $aulas,
            'buscar' => $buscar,
            'tipo' => $tipo,
            'tipos' => Salon::TIPOS,
        ]);
    }

    public function create()
    {
        return view('admin.aulas.create', ['tipos' => Salon::TIPOS]);
    }

    public function store(StoreSalonRequest $request): RedirectResponse
    {
        try {
            $aula = Salon::create($request->validated());
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('registrar aula', $error)->withInput();
        }

        return redirect()
            ->route('admin.aulas.index')
            ->with('exito', "El aula {$aula->descripcion} del edificio {$aula->edificio} se registró correctamente.");
    }

    public function edit(Salon $salon)
    {
        return view('admin.aulas.edit', ['salon' => $salon, 'tipos' => Salon::TIPOS]);
    }

    public function update(UpdateSalonRequest $request, Salon $salon): RedirectResponse
    {
        try {
            $salon->update($request->validated());
        } catch (QueryException $error) {
            return $this->falloDeBaseDeDatos('actualizar aula', $error)->withInput();
        }

        return redirect()
            ->route('admin.aulas.index')
            ->with('exito', "El aula {$salon->descripcion} del edificio {$salon->edificio} se actualizó correctamente.");
    }

    // Registra el detalle técnico y devuelve un mensaje genérico
    private function falloDeBaseDeDatos(string $accion, QueryException $error): RedirectResponse
    {
        Log::error("Error al {$accion}", ['detalle' => $error->errorInfo[2] ?? $error->getCode()]);

        return back()->with('error', self::MENSAJE_ERROR);
    }
}
