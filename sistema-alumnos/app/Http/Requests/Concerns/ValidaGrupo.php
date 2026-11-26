<?php

namespace App\Http\Requests\Concerns;

use App\Models\Alumno;
use App\Models\Grupo;
use Illuminate\Validation\Rule;

/** Reglas y mensajes compartidos por el alta y la edición de grupos. */
trait ValidaGrupo
{
    // Letra en mayúscula; la especialidad solo existe en 5.º y 6.º (CHECK ck_grupo_especialidad)
    protected function normalizarGrupo(): void
    {
        $semestre = (int) $this->input('semestre');
        $especialidad = $this->input('id_especialidad');

        $this->merge([
            'letra' => mb_strtoupper(trim((string) $this->input('letra'))),
            'id_especialidad' => Alumno::semestreRequiereEspecialidad($semestre) && filled($especialidad) ? $especialidad : null,
        ]);
    }

    protected function reglasSemestre(): array
    {
        return ['required', 'integer', 'between:' . Alumno::SEMESTRE_MINIMO . ',' . Alumno::SEMESTRE_MAXIMO];
    }

    protected function reglasEspecialidad(): array
    {
        return [
            Rule::requiredIf(fn () => Alumno::semestreRequiereEspecialidad((int) $this->input('semestre'))),
            'nullable', 'integer', 'exists:especialidad,id',
        ];
    }

    // Letra A–Z, única por (periodo, semestre). $idGrupoActual excluye al grupo que se edita.
    protected function reglasLetra(int|string|null $idPeriodo, ?int $idGrupoActual = null): array
    {
        $reglas = ['required', 'string', 'size:1', 'regex:/^[A-Z]$/'];

        // Sin periodo o semestre válidos no se consulta la BD (los otros campos ya marcan su error)
        if (is_numeric($idPeriodo) && is_numeric($this->input('semestre'))) {
            $reglas[] = Rule::unique('grupo', 'letra')
                ->where(fn ($consulta) => $consulta
                    ->where('id_periodo', (int) $idPeriodo)
                    ->where('semestre', (int) $this->input('semestre')))
                ->ignore($idGrupoActual);
        }

        return $reglas;
    }

    protected function mensajesGrupo(string $nombrePeriodo): array
    {
        $nombreGrupo = Alumno::nombreGrupo((int) $this->input('semestre'), (string) $this->input('letra'));

        return [
            'id_periodo.required' => 'Selecciona el periodo.',
            'id_periodo.exists' => 'El periodo seleccionado no existe.',
            'semestre.required' => 'Selecciona el semestre.',
            'semestre.integer' => 'El semestre no es válido.',
            'semestre.between' => 'El semestre debe estar entre 1 y 6.',
            'letra.required' => 'Selecciona la letra del grupo.',
            'letra.size' => 'La letra debe ser un solo carácter.',
            'letra.regex' => 'La letra debe ir de la A a la Z.',
            'letra.unique' => "El grupo {$nombreGrupo} ya está registrado en el periodo {$nombrePeriodo}.",
            'id_especialidad.required' => 'Selecciona la especialidad: los grupos de 5.º y 6.º semestre la llevan.',
            'id_especialidad.exists' => 'La especialidad seleccionada no existe.',
        ];
    }
}