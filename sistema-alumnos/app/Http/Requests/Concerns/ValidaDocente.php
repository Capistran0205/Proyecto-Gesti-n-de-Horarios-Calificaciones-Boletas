<?php

namespace App\Http\Requests\Concerns;

use App\Models\Docente;
use App\Models\User;

/** Normalización, reglas y mensajes compartidos por el alta y la edición de docentes. */
trait ValidaDocente
{
    // Nombres sin espacios sobrantes, correo en minúsculas, CURP y RFC en mayúsculas.
    // Los estudios completamente vacíos (filas agregadas y no llenadas) se descartan.
    protected function normalizarDocente(): array
    {
        $estudios = collect($this->input('estudios', []))
            ->filter(fn ($estudio) => is_array($estudio))
            ->map(fn ($estudio) => [
                'descripcion' => $this->limpiarTexto($estudio['descripcion'] ?? ''),
                'grado_estudio' => trim((string) ($estudio['grado_estudio'] ?? '')),
            ])
            ->reject(fn ($estudio) => $estudio['descripcion'] === '' && $estudio['grado_estudio'] === '')
            ->values()
            ->all();

        return [
            'nombre' => $this->limpiarTexto($this->input('nombre')),
            'apellido_pat' => $this->limpiarTexto($this->input('apellido_pat')),
            'apellido_mat' => $this->limpiarTexto($this->input('apellido_mat')) ?: null,
            'correo_electronico' => mb_strtolower(trim((string) $this->input('correo_electronico'))),
            'curp' => mb_strtoupper(trim((string) $this->input('curp'))),
            'rfc' => mb_strtoupper(trim((string) $this->input('rfc'))),
            'estudios' => $estudios,
        ];
    }

    // Quita espacios de los extremos y colapsa los repetidos
    protected function limpiarTexto($valor): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $valor));
    }

    protected function reglasDocente(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:60', 'regex:' . User::REGEX_NOMBRE_PERSONA],
            'apellido_pat' => ['required', 'string', 'max:60', 'regex:' . User::REGEX_NOMBRE_PERSONA],
            'apellido_mat' => ['nullable', 'string', 'max:60', 'regex:' . User::REGEX_NOMBRE_PERSONA],
            // La unicidad se revisa en ValidaDuplicadosDocente para avisar si el dato es de un docente de baja
            'correo_electronico' => ['required', 'email', 'max:120'],
            'curp' => ['required', 'regex:' . Docente::REGEX_CURP],
            'rfc' => ['required', 'regex:' . Docente::REGEX_RFC],
            'id_area_disciplinar' => ['required', 'integer', 'exists:area_disciplinar,id'],
            'horas_plaza' => ['required', 'integer', 'between:' . Docente::HORAS_PLAZA_MINIMA . ',' . Docente::HORAS_TIEMPO_COMPLETO],
            'estudios' => ['required', 'array', 'min:1', 'max:4'],
            'estudios.*.descripcion' => ['required', 'string', 'max:120'],
            'estudios.*.grado_estudio' => ['required', 'integer', 'between:1,5'],
        ];
    }

    protected function mensajesDocente(): array
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
            'rfc.required' => 'Escribe el RFC.',
            'rfc.regex' => 'El RFC no tiene un formato válido (13 caracteres).',
            'id_area_disciplinar.required' => 'Selecciona el área disciplinar.',
            'id_area_disciplinar.integer' => 'El área disciplinar no es válida.',
            'id_area_disciplinar.exists' => 'El área disciplinar no es válida.',
            'horas_plaza.required' => 'Escribe las horas de plaza.',
            'horas_plaza.integer' => 'Las horas de plaza deben ser un número entero.',
            'horas_plaza.between' => 'Las horas de plaza deben estar entre 2 y 40.',
            'estudios.required' => 'Registra al menos un estudio.',
            'estudios.array' => 'Los estudios no son válidos.',
            'estudios.min' => 'Registra al menos un estudio.',
            'estudios.max' => 'Solo se pueden registrar hasta 4 estudios.',
            'estudios.*.descripcion.required' => 'Escribe la descripción de cada estudio.',
            'estudios.*.descripcion.max' => 'La descripción de un estudio no puede pasar de 120 caracteres.',
            'estudios.*.grado_estudio.required' => 'Selecciona el grado de cada estudio.',
            'estudios.*.grado_estudio.integer' => 'El grado de un estudio no es válido.',
            'estudios.*.grado_estudio.between' => 'El grado de un estudio no es válido.',
        ];
    }
}
