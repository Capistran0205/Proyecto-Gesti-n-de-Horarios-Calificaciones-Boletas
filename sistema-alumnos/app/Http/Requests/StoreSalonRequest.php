<?php

namespace App\Http\Requests;

use App\Models\Salon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalonRequest extends FormRequest
{
    // Quita espacios de los extremos y colapsa los repetidos
    protected function prepareForValidation(): void
    {
        $this->merge([
            'descripcion' => preg_replace('/\s+/u', ' ', trim((string) $this->input('descripcion'))),
            'edificio' => preg_replace('/\s+/u', ' ', trim((string) $this->input('edificio'))),
        ]);
    }

    public function rules(): array
    {
        return [
            // Único por (edificio, descripcion), igual que uq_salon_edificio
            'descripcion' => [
                'required', 'string', 'max:40',
                Rule::unique('salon', 'descripcion')->where('edificio', $this->input('edificio')),
            ],
            'edificio' => ['required', 'string', 'max:20'],
            'tipo' => ['required', 'integer', Rule::in(array_keys(Salon::TIPOS))],
        ];
    }

    public function messages(): array
    {
        return [
            'descripcion.required' => 'Escribe la descripción del aula.',
            'descripcion.max' => 'La descripción no puede pasar de 40 caracteres.',
            'descripcion.unique' => 'Ya existe un aula con esa descripción en ese edificio.',
            'edificio.required' => 'Escribe el edificio.',
            'edificio.max' => 'El edificio no puede pasar de 20 caracteres.',
            'tipo.required' => 'Selecciona el tipo de aula.',
            'tipo.in' => 'El tipo de aula no es válido.',
            'tipo.integer' => 'El tipo de aula no es válido.',
        ];
    }
}
