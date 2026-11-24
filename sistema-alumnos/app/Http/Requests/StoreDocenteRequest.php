<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaDisponibilidad;
use App\Http\Requests\Concerns\ValidaDocente;
use App\Http\Requests\Concerns\ValidaDuplicadosDocente;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocenteRequest extends FormRequest
{
    use ValidaDocente, ValidaDuplicadosDocente, ValidaDisponibilidad;

    protected function prepareForValidation(): void
    {
        $this->merge($this->normalizarDocente() + ['usuario' => trim((string) $this->input('usuario'))]);
        $this->merge($this->normalizarHorasDisponibilidad());
    }

    // La disponibilidad semanal es obligatoria en el alta (se define desde el formulario)
    public function rules(): array
    {
        return $this->reglasDocente() + $this->reglasDisponibilidad() + [
            'usuario' => ['required', 'digits_between:4,30'],
            'contrasena' => ['required', 'string', 'min:8'],
        ];
    }

    public function withValidator(Validator $validador): void
    {
        $this->validarDuplicados($validador, null, true);
        $this->validarDisponibilidad($validador);
    }

    public function messages(): array
    {
        return $this->mensajesDocente() + $this->mensajesDisponibilidad() + [
            'usuario.required' => 'Escribe el número de empleado.',
            'usuario.digits_between' => 'El número de empleado solo puede contener números (de 4 a 30 dígitos).',
            'contrasena.required' => 'La contraseña temporal es obligatoria.',
            'contrasena.min' => 'La contraseña temporal debe tener al menos 8 caracteres.',
        ];
    }
}
