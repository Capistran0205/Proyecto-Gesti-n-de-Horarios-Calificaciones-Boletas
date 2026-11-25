<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaAlumno;
use App\Http\Requests\Concerns\ValidaDuplicadosAlumno;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

// La matrícula no se edita (es el usuario con el que inicia sesión) y la contraseña tampoco se toca aquí.
// Con grupo en el periodo vigente tampoco se cambian el semestre ni la especialidad.
class UpdateAlumnoRequest extends FormRequest
{
    use ValidaAlumno, ValidaDuplicadosAlumno;

    protected function prepareForValidation(): void
    {
        $datos = $this->normalizarAlumno();

        // Con grupo en el periodo vigente el semestre y la especialidad no cambian (el formulario no los envía
        // porque los muestra deshabilitados): se conservan los guardados, aunque alguien manipule la petición.
        $alumno = $this->route('alumno')->alumno;
        if ($alumno && $alumno->grupoVigente()) {
            $datos['semestre'] = (string) $alumno->semestre;
            $datos['id_especialidad'] = $alumno->id_especialidad === null ? null : (string) $alumno->id_especialidad;
        }

        $this->merge($datos);
    }

    public function rules(): array
    {
        return $this->reglasAlumno();
    }

    public function withValidator(Validator $validador): void
    {
        // {alumno} de la ruta ya viene resuelto como modelo User: se excluye a sí mismo
        $this->validarDuplicados($validador, $this->route('alumno')->id, false);
    }

    public function messages(): array
    {
        return $this->mensajesAlumno();
    }
}
