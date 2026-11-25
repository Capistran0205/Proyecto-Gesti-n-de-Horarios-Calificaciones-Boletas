<?php

namespace App\Http\Requests\Concerns;

use App\Models\Alumno;
use App\Models\User;

/** Normalización, reglas y mensajes compartidos por el alta, la edición y la reactivación de alumnos. */
trait ValidaAlumno
{
    // Nombres sin espacios sobrantes, correo en minúsculas, CURP en mayúsculas y NSS vacío = sin NSS
    protected function normalizarAlumno(): array
    {
        $nss = trim((string) $this->input('nss'));

        return [
            'nombre' => $this->limpiarTexto($this->input('nombre')),
            'apellido_pat' => $this->limpiarTexto($this->input('apellido_pat')),
            'apellido_mat' => $this->limpiarTexto($this->input('apellido_mat')) ?: null,
            'correo_electronico' => mb_strtolower(trim((string) $this->input('correo_electronico'))),
            'curp' => mb_strtoupper(trim((string) $this->input('curp'))),
            'nss' => $nss === '' ? null : $nss,
            'anio_ingreso' => trim((string) $this->input('anio_ingreso')),
        ] + $this->normalizarSemestreYEspecialidad();
    }

    // En 1.º a 4.º no hay especialidad: se fuerza a null aunque el formulario la envíe
    protected function normalizarSemestreYEspecialidad(): array
    {
        $semestre = trim((string) $this->input('semestre'));
        $especialidad = trim((string) $this->input('id_especialidad'));

        if ($especialidad === '' || (ctype_digit($semestre) && ! Alumno::semestreRequiereEspecialidad((int) $semestre))) {
            $especialidad = null;
        }

        return ['semestre' => $semestre, 'id_especialidad' => $especialidad];
    }

    // Quita espacios de los extremos y colapsa los repetidos
    protected function limpiarTexto($valor): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $valor));
    }

    protected function reglasAlumno(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:60', 'regex:' . User::REGEX_NOMBRE_PERSONA],
            'apellido_pat' => ['required', 'string', 'max:60', 'regex:' . User::REGEX_NOMBRE_PERSONA],
            'apellido_mat' => ['nullable', 'string', 'max:60', 'regex:' . User::REGEX_NOMBRE_PERSONA],
            // La unicidad se revisa en ValidaDuplicadosAlumno para avisar si el dato es de un alumno de baja
            'correo_electronico' => ['required', 'email', 'max:120'],
            'curp' => ['required', 'regex:' . Alumno::REGEX_CURP],
            'nss' => ['nullable', 'digits:11'],
            'anio_ingreso' => ['required', 'integer', 'between:' . Alumno::ANIO_INGRESO_MINIMO . ',' . Alumno::ANIO_INGRESO_MAXIMO],
        ] + $this->reglasSemestreYEspecialidad();
    }

    // Especialidad obligatoria en 5.º y 6.º, prohibida en 1.º a 4.º (mismo CHECK que el esquema)
    protected function reglasSemestreYEspecialidad(): array
    {
        return [
            'semestre' => ['required', 'integer', 'between:' . Alumno::SEMESTRE_MINIMO . ',' . Alumno::SEMESTRE_MAXIMO],
            'id_especialidad' => [
                'nullable',
                'required_if:semestre,5,6',
                'prohibited_if:semestre,1,2,3,4',
                'integer',
                'exists:especialidad,id',
            ],
        ];
    }

    protected function mensajesAlumno(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre.',
            'nombre.max' => 'El nombre no puede pasar de 60 caracteres.',
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
            'apellido_pat.required' => 'Escribe el apellido paterno.',
            'apellido_pat.max' => 'El apellido paterno no puede pasar de 60 caracteres.',
            'apellido_pat.regex' => 'El apellido paterno solo puede contener letras y espacios.',
            'apellido_mat.max' => 'El apellido materno no puede pasar de 60 caracteres.',
            'apellido_mat.regex' => 'El apellido materno solo puede contener letras y espacios.',
            'correo_electronico.required' => 'Escribe el correo electrónico.',
            'correo_electronico.email' => 'Escribe un correo electrónico válido.',
            'correo_electronico.max' => 'El correo no puede pasar de 120 caracteres.',
            'curp.required' => 'Escribe la CURP.',
            'curp.regex' => 'La CURP no tiene un formato válido (18 caracteres).',
            'nss.digits' => 'El NSS debe tener exactamente 11 dígitos.',
            'anio_ingreso.required' => 'Escribe el año de ingreso.',
            'anio_ingreso.integer' => 'El año de ingreso debe ser un número entero.',
            'anio_ingreso.between' => 'El año de ingreso debe estar entre 2000 y 2100.',
        ] + $this->mensajesSemestreYEspecialidad();
    }

    protected function mensajesSemestreYEspecialidad(): array
    {
        return [
            'semestre.required' => 'Selecciona el semestre.',
            'semestre.integer' => 'El semestre no es válido.',
            'semestre.between' => 'El semestre debe estar entre 1 y 6.',
            'id_especialidad.required_if' => 'Selecciona la especialidad: en 5.º y 6.º semestre es obligatoria.',
            'id_especialidad.prohibited_if' => 'Los alumnos de 1.º a 4.º semestre no llevan especialidad.',
            'id_especialidad.integer' => 'La especialidad no es válida.',
            'id_especialidad.exists' => 'La especialidad no es válida.',
        ];
    }
}
