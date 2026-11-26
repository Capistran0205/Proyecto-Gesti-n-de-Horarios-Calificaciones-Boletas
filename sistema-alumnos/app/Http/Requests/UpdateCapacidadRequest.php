<?php

namespace App\Http\Requests;

use App\Models\Grupo;
use Illuminate\Foundation\Http\FormRequest;

// Capacidad: entero entre los alumnos ya inscritos y el máximo de la columna SMALLINT.
// El controlador vuelve a comprobarlo con el grupo bloqueado, por si se inscribe alguien en este momento.
class UpdateCapacidadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'capacidad_max' => ['required', 'integer', 'min:' . max(1, $this->inscritos()), 'max:' . Grupo::CAPACIDAD_MAXIMA_BD],
        ];
    }

    public function messages(): array
    {
        return [
            'capacidad_max.required' => 'Escribe la capacidad máxima.',
            'capacidad_max.integer' => 'La capacidad debe ser un número entero.',
            'capacidad_max.min' => 'La capacidad no puede ser menor a los alumnos ya inscritos (' . $this->inscritos() . ') ni menor a 1.',
            'capacidad_max.max' => 'La capacidad no puede pasar de ' . Grupo::CAPACIDAD_MAXIMA_BD . ' alumnos.',
        ];
    }

    private function inscritos(): int
    {
        return $this->route('grupo')->alumnos()->count();
    }
}