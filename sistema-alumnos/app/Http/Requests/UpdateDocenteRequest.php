<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaDocente;
use App\Http\Requests\Concerns\ValidaDuplicadosDocente;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

// El número de empleado no se edita (es el usuario con el que inicia sesión).
// La contraseña, la disponibilidad y las materias tampoco se tocan aquí.
class UpdateDocenteRequest extends FormRequest
{
    use ValidaDocente, ValidaDuplicadosDocente;

    protected function prepareForValidation(): void
    {
        $this->merge($this->normalizarDocente());
    }

    public function rules(): array
    {
        return $this->reglasDocente();
    }

    public function withValidator(Validator $validador): void
    {
        // {docente} de la ruta ya viene resuelto como modelo User: se excluye a sí mismo
        $this->validarDuplicados($validador, $this->route('docente')->id, false);
    }

    public function messages(): array
    {
        return $this->mensajesDocente();
    }
}
