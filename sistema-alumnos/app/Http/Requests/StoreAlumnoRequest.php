<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaAlumno;
use App\Http\Requests\Concerns\ValidaDuplicadosAlumno;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreAlumnoRequest extends FormRequest
{
    use ValidaAlumno, ValidaDuplicadosAlumno;

    protected function prepareForValidation(): void
    {
        $this->merge($this->normalizarAlumno() + ['usuario' => trim((string) $this->input('usuario'))]);
    }

    public function rules(): array
    {
        return $this->reglasAlumno() + [
            // Matrícula de formato libre: sin espacios, hasta 30 caracteres (tamaño de usuario.usuario)
            'usuario' => ['required', 'string', 'max:30', 'regex:/^\S+$/u'],
            'contrasena' => ['required', 'string', 'min:8'],
        ];
    }

    public function withValidator(Validator $validador): void
    {
        $this->validarDuplicados($validador, null, true);
    }

    public function messages(): array
    {
        return $this->mensajesAlumno() + [
            'usuario.required' => 'Escribe la matrícula.',
            'usuario.max' => 'La matrícula no puede pasar de 30 caracteres.',
            'usuario.regex' => 'La matrícula no puede contener espacios.',
            'contrasena.required' => 'La contraseña temporal es obligatoria.',
            'contrasena.min' => 'La contraseña temporal debe tener al menos 8 caracteres.',
        ];
    }
}
