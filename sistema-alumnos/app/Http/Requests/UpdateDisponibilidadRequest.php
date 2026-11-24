<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaDisponibilidad;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDisponibilidadRequest extends FormRequest
{
    use ValidaDisponibilidad;

    protected function prepareForValidation(): void
    {
        $this->merge($this->normalizarHorasDisponibilidad());
    }

    public function rules(): array
    {
        return $this->reglasDisponibilidad();
    }

    public function withValidator(Validator $validador): void
    {
        $this->validarDisponibilidad($validador);
    }

    public function messages(): array
    {
        return $this->mensajesDisponibilidad();
    }
}
