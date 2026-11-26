<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaGrupo;
use App\Models\PeriodoEscolar;
use Illuminate\Foundation\Http\FormRequest;

// La capacidad no se recibe: el administrador siempre crea el grupo con 40 (el controlador la fija)
class StoreGrupoRequest extends FormRequest
{
    use ValidaGrupo;

    protected function prepareForValidation(): void
    {
        $this->normalizarGrupo();
    }

    public function rules(): array
    {
        return [
            'id_periodo' => ['required', 'integer', 'exists:periodo_escolar,id'],
            'semestre' => $this->reglasSemestre(),
            'letra' => $this->reglasLetra($this->input('id_periodo')),
            'id_especialidad' => $this->reglasEspecialidad(),
        ];
    }

    public function messages(): array
    {
        $periodo = is_numeric($this->input('id_periodo')) ? PeriodoEscolar::find((int) $this->input('id_periodo')) : null;

        return $this->mensajesGrupo($periodo?->nombre ?? '');
    }
}