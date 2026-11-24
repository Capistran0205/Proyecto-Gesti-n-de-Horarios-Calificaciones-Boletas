<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDisponibilidadRequest;
use App\Models\PeriodoEscolar;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Captura de la disponibilidad semanal de un docente ya registrado (en el alta se define desde el formulario).
 * La interfaz trabaja con un rango "Desde-Hasta" por día; se guarda como bloques de clase (1-7) en
 * docente.disponibilidad: { "día": [bloques en los que sí puede dar clase] }. Los días y bloques salen de config/horario.php.
 * No se puede modificar mientras el docente tenga clases en el horario del periodo vigente.
 */
class DisponibilidadDocenteController extends Controller
{
    private const MENSAJE_ERROR = 'No se pudo completar la operación. Inténtalo de nuevo; si el problema continúa, avisa al responsable del sistema.';

    public function edit(User $docente)
    {
        $this->validarDocente($docente);

        if ($respuesta = $this->noSePuedeModificar($docente)) {
            return $respuesta;
        }

        return view('admin.docentes.disponibilidad', [
            'docente' => $docente,
            'rangos' => $docente->docente->rangosPorDia(), // [día => ['activo', 'desde', 'hasta']]; NULL = sin captura previa
            'dias' => config('horario.dias'),
            'horarioEscolar' => $this->horarioEscolar(),
        ]);
    }

    public function update(UpdateDisponibilidadRequest $request, User $docente): RedirectResponse
    {
        $this->validarDocente($docente);

        if ($respuesta = $this->noSePuedeModificar($docente)) {
            return $respuesta;
        }

        try {
            $docente->docente->update(['disponibilidad' => $request->disponibilidadNormalizada()]);
        } catch (QueryException $error) {
            Log::error('Error al guardar la disponibilidad del docente', ['detalle' => $error->errorInfo[2] ?? $error->getCode()]);

            return back()->with('error', self::MENSAJE_ERROR);
        }

        return redirect()
            ->route('admin.docentes.index')
            ->with('exito', "La disponibilidad de {$docente->nombre_completo} se guardó correctamente.");
    }

    // Un docente de baja no se modifica; uno con horario asignado en el periodo vigente tampoco su disponibilidad
    private function noSePuedeModificar(User $docente): ?RedirectResponse
    {
        if ($docente->estado === User::ESTADO_BAJA) {
            return redirect()->route('admin.docentes.index')
                ->with('error', 'El docente está de baja. Reactívalo para poder modificarlo.');
        }

        if ($docente->docente->tieneClasesEnPeriodo(PeriodoEscolar::vigente()?->id)) {
            return redirect()->route('admin.docentes.index')
                ->with('error', 'No se puede modificar la disponibilidad mientras el docente tenga horario asignado.');
        }

        return null;
    }

    // Primer inicio y último fin de los bloques: el horario escolar
    private function horarioEscolar(): array
    {
        $bloques = config('horario.bloques');

        return [
            'inicio' => $bloques[array_key_first($bloques)]['inicio'],
            'fin' => $bloques[array_key_last($bloques)]['fin'],
        ];
    }

    private function validarDocente(User $usuario): void
    {
        if ((int) $usuario->rol !== User::ROL_DOCENTE || ! $usuario->docente) {
            abort(404);
        }
    }
}
