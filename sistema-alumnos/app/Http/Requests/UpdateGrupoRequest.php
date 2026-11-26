<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaGrupo;
use Illuminate\Foundation\Http\FormRequest;

// El administrador cambia letra, y semestre/especialidad solo si el grupo no tiene alumnos.
// El periodo y la capacidad no se editan aquí (la capacidad es del SuperUsuario).
class UpdateGrupoRequest extends FormRequest
{
    use ValidaGrupo;

    protected function prepareForValidation(): void
    {
        $grupo = $this->route('grupo');

        // Con alumnos inscritos se ignora lo que llegue de semestre y especialidad
        if ($grupo->alumnos()->exists()) {
            $this->merge([
                'semestre' => $grupo->semestre,
                'id_especialidad' => $grupo->id_especialidad,
            ]);
        }

        $this->normalizarGrupo();
    }

    public function rules(): array
    {
        $grupo = $this->route('grupo');

        return [
            'semestre' => $this->reglasSemestre(),
            'letra' => $this->reglasLetra($grupo->id_periodo, $grupo->id),
            'id_especialidad' => $this->reglasEspecialidad(),
        ];
    }

    public function messages(): array
    {
        return $this->mensajesGrupo($this->route('grupo')->periodo->nombre);
    }
}