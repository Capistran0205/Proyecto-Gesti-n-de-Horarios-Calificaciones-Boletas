<?php

namespace App\Http\Requests\Concerns;

use App\Models\Materia;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\Unique;

/** Reglas y mensajes compartidos por el alta y la edición de materias. */
trait ValidaMateria
{
    // Nombre sin espacios sobrantes (sin restricción de caracteres), clave en mayúsculas
    // y especialidad vacía cuando la materia es de tronco común
    protected function normalizarMateria(): void
    {
        $datos = [
            'nombre' => preg_replace('/\s+/u', ' ', trim((string) $this->input('nombre'))),
            'clave' => mb_strtoupper(trim((string) $this->input('clave'))),
        ];

        if ((string) $this->input('tipo') === (string) Materia::TIPO_TRONCO_COMUN) {
            $datos['id_especialidad'] = null;
        } elseif ($this->input('id_especialidad') === '') {
            $datos['id_especialidad'] = null;
        }

        $this->merge($datos);
    }

    protected function reglasMateria(Unique $claveUnica): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'clave' => ['required', 'string', 'max:20', $claveUnica],
            'horas_semana' => ['required', 'integer', 'between:1,10'],
            'tipo' => ['required', 'integer', 'in:' . Materia::TIPO_TRONCO_COMUN . ',' . Materia::TIPO_ESPECIALIDAD],
            'semestre' => ['required', 'integer', 'between:1,6'],
            'id_area_disciplinar' => ['required', 'integer', 'exists:area_disciplinar,id'],
            'id_especialidad' => [
                'nullable',
                'required_if:tipo,' . Materia::TIPO_ESPECIALIDAD,
                'prohibited_if:tipo,' . Materia::TIPO_TRONCO_COMUN,
                'integer',
                'exists:especialidad,id',
            ],
        ];
    }

    // Reglas que dependen de varios campos. $idActual excluye a la materia que se edita.
    protected function validarMateria(Validator $validador, ?int $idActual): void
    {
        $validador->after(function (Validator $validador) use ($idActual) {
            $errores = $validador->errors();
            $datos = $validador->getData();

            // Las materias de especialidad solo existen en 5.º y 6.º (mismo CHECK que el esquema)
            if (! $errores->hasAny(['tipo', 'semestre'])
                && (int) $datos['tipo'] === Materia::TIPO_ESPECIALIDAD && (int) $datos['semestre'] < 5) {
                $errores->add('semestre', 'Las materias de especialidad solo existen en 5.º y 6.º semestre.');
            }

            // El nombre no se repite dentro del mismo semestre (sin distinguir mayúsculas; cuenta también las de baja)
            if (! $errores->hasAny(['nombre', 'semestre'])) {
                $repetida = Materia::where('semestre', (int) $datos['semestre'])
                    ->whereRaw('lower(nombre) = ?', [mb_strtolower($datos['nombre'])])
                    ->when($idActual, fn ($consulta) => $consulta->where('id', '!=', $idActual))
                    ->first();

                if ($repetida) {
                    $errores->add('nombre', "Ya existe una materia llamada «{$repetida->nombre}» en el semestre {$repetida->semestre}.");
                }
            }
        });
    }

    protected function mensajesMateria(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre de la materia.',
            'nombre.max' => 'El nombre no puede pasar de 120 caracteres.',
            'clave.required' => 'Escribe la clave de la materia.',
            'clave.max' => 'La clave no puede pasar de 20 caracteres.',
            'clave.unique' => 'La clave :input ya está registrada.',
            'horas_semana.required' => 'Escribe las horas semanales.',
            'horas_semana.integer' => 'Las horas semanales deben ser un número entero.',
            'horas_semana.between' => 'Las horas semanales deben estar entre 1 y 10.',
            'tipo.required' => 'Selecciona el tipo de materia.',
            'tipo.integer' => 'El tipo de materia no es válido.',
            'tipo.in' => 'El tipo de materia no es válido.',
            'semestre.required' => 'Selecciona el semestre.',
            'semestre.integer' => 'El semestre no es válido.',
            'semestre.between' => 'El semestre debe estar entre 1 y 6.',
            'id_area_disciplinar.required' => 'Selecciona el área disciplinar.',
            'id_area_disciplinar.integer' => 'El área disciplinar no es válida.',
            'id_area_disciplinar.exists' => 'El área disciplinar no es válida.',
            'id_especialidad.required_if' => 'Selecciona la especialidad: las materias de especialidad la requieren.',
            'id_especialidad.prohibited_if' => 'Las materias de tronco común no llevan especialidad.',
            'id_especialidad.integer' => 'La especialidad no es válida.',
            'id_especialidad.exists' => 'La especialidad no es válida.',
        ];
    }
}
