<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaPeriodoEscolar;
use App\Models\PeriodoEscolar;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StorePeriodoRequest extends FormRequest
{
    use ValidaPeriodoEscolar;

    protected function prepareForValidation(): void
    {
        $this->normalizarNombrePeriodo();
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:40', 'regex:' . PeriodoEscolar::regexNombre(), 'unique:periodo_escolar,nombre'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after:fecha_inicio'],
        ];
    }

    public function withValidator(Validator $validador): void
    {
        $this->validarTraslape($validador, null);
    }

    public function messages(): array
    {
        return $this->mensajesPeriodo();
    }
}
