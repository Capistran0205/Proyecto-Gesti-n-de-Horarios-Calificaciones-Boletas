<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaMateria;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMateriaRequest extends FormRequest
{
    use ValidaMateria;

    protected function prepareForValidation(): void
    {
        $this->normalizarMateria();
    }

    public function rules(): array
    {
        // {materia} de la ruta ya viene resuelto como modelo: la materia no choca consigo misma
        return $this->reglasMateria(Rule::unique('materia', 'clave')->ignore($this->route('materia')->id));
    }

    public function withValidator(Validator $validador): void
    {
        $this->validarMateria($validador, $this->route('materia')->id);
    }

    public function messages(): array
    {
        return $this->mensajesMateria();
    }
}
