<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Solo valida la forma de la lista; la elegibilidad y el cupo se revisan en el controlador, dentro de la transacción
class AsignarAlumnosRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'alumnos' => ['required', 'array', 'min:1'],
            'alumnos.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'alumnos.required' => 'Selecciona al menos un alumno.',
            'alumnos.array' => 'La selección de alumnos no es válida.',
            'alumnos.min' => 'Selecciona al menos un alumno.',
            'alumnos.*.integer' => 'La selección de alumnos no es válida.',
            'alumnos.*.distinct' => 'Un alumno aparece más de una vez en la selección.',
        ];
    }
}