<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaAlumno;
use Illuminate\Foundation\Http\FormRequest;

// Al reactivar, el administrador actualiza el semestre (y la especialidad si queda en 5.º o 6.º)
class ReactivarAlumnoRequest extends FormRequest
{
    use ValidaAlumno;

    protected function prepareForValidation(): void
    {
        $this->merge($this->normalizarSemestreYEspecialidad());
    }

    public function rules(): array
    {
        return $this->reglasSemestreYEspecialidad();
    }

    public function messages(): array
    {
        return $this->mensajesSemestreYEspecialidad();
    }
}
