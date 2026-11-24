<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaMateria;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMateriaRequest extends FormRequest
{
    use ValidaMateria;

    protected function prepareForValidation(): void
    {
        $this->normalizarMateria();
    }

    public function rules(): array
    {
        return $this->reglasMateria(Rule::unique('materia', 'clave'));
    }

    public function withValidator(Validator $validador): void
    {
        $this->validarMateria($validador, null);
    }

    public function messages(): array
    {
        return $this->mensajesMateria();
    }
}
