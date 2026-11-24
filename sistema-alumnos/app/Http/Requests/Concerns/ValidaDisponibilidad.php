<?php

namespace App\Http\Requests\Concerns;

use App\Models\Docente;
use Illuminate\Contracts\Validation\Validator;

/**
 * Disponibilidad semanal que captura la interfaz: por día, una casilla y un rango "Desde-Hasta":
 *   disponibilidad[día][activo]=1, disponibilidad[día][desde]="15:00", disponibilidad[día][hasta]="21:20".
 * Se guarda en docente.disponibilidad como bloques de clase: { "día": [bloques completamente dentro del rango] }.
 * Un día sin casilla marcada no llega en la petición y queda como [].
 */
trait ValidaDisponibilidad
{
    // El navegador puede enviar "15:00:00"; se deja en "15:00"
    protected function normalizarHorasDisponibilidad(): array
    {
        $disponibilidad = $this->input('disponibilidad');

        if (! is_array($disponibilidad)) {
            return [];
        }

        foreach ($disponibilidad as $dia => $datos) {
            if (! is_array($datos)) {
                continue;
            }

            foreach (['desde', 'hasta'] as $campo) {
                if (isset($datos[$campo]) && is_string($datos[$campo]) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $datos[$campo])) {
                    $disponibilidad[$dia][$campo] = substr($datos[$campo], 0, 5);
                }
            }
        }

        return ['disponibilidad' => $disponibilidad];
    }

    protected function reglasDisponibilidad(): array
    {
        return [
            'disponibilidad' => ['required', 'array'],
            'disponibilidad.*' => ['array'],
            'disponibilidad.*.activo' => ['nullable', 'boolean'], // sin regla, validated() lo descartaría
            'disponibilidad.*.desde' => ['nullable', 'date_format:H:i'],
            'disponibilidad.*.hasta' => ['nullable', 'date_format:H:i'],
        ];
    }

    protected function validarDisponibilidad(Validator $validador): void
    {
        $validador->after(function (Validator $validador) {
            foreach ($validador->errors()->keys() as $llave) {
                if (str_starts_with($llave, 'disponibilidad')) {
                    return; // primero se corrigen los errores de formato
                }
            }

            $dias = config('horario.dias');
            $bloques = config('horario.bloques');
            $inicioEscolar = $bloques[array_key_first($bloques)]['inicio'];
            $finEscolar = $bloques[array_key_last($bloques)]['fin'];
            $hayDia = false;

            foreach ($validador->getData()['disponibilidad'] ?? [] as $dia => $datos) {
                if (! isset($dias[(int) $dia]) || (string) (int) $dia !== (string) $dia) {
                    $validador->errors()->add('disponibilidad', 'La disponibilidad solo admite los días de lunes a viernes.');
                    return;
                }

                if (! filter_var($datos['activo'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }

                $hayDia = true;
                $nombre = $dias[(int) $dia];
                $desde = $datos['desde'] ?? null;
                $hasta = $datos['hasta'] ?? null;

                if (! $desde || ! $hasta) {
                    $validador->errors()->add('disponibilidad', "{$nombre}: indica la hora «Desde» y la hora «Hasta».");
                } elseif ($desde < $inicioEscolar || $hasta > $finEscolar) {
                    $validador->errors()->add('disponibilidad', "{$nombre}: el horario debe estar entre {$inicioEscolar} y {$finEscolar}.");
                } elseif ($hasta <= $desde) {
                    $validador->errors()->add('disponibilidad', "{$nombre}: la hora «Hasta» debe ser mayor que «Desde».");
                } elseif (count(Docente::bloquesDelRango($desde, $hasta)) === 0) {
                    $validador->errors()->add('disponibilidad', "{$nombre}: el rango debe incluir al menos un bloque de clase completo (50 minutos).");
                }
            }

            if (! $hayDia) {
                $validador->errors()->add('disponibilidad', 'Selecciona al menos un día de disponibilidad.');
            }
        });
    }

    // Siempre las cinco claves (los días sin casilla llevan []), con los bloques ordenados
    public function disponibilidadNormalizada(): array
    {
        $enviada = $this->validated()['disponibilidad'] ?? [];
        $resultado = [];

        foreach (array_keys(config('horario.dias')) as $dia) {
            $datos = $enviada[$dia] ?? [];
            $activo = filter_var($datos['activo'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $resultado[$dia] = $activo ? Docente::bloquesDelRango($datos['desde'], $datos['hasta']) : [];
        }

        return $resultado;
    }

    protected function mensajesDisponibilidad(): array
    {
        return [
            'disponibilidad.required' => 'Define la disponibilidad semanal: selecciona al menos un día.',
            'disponibilidad.array' => 'La disponibilidad no es válida.',
            'disponibilidad.*.array' => 'La disponibilidad de un día no es válida.',
            'disponibilidad.*.activo.boolean' => 'La casilla de un día no es válida.',
            'disponibilidad.*.desde.date_format' => 'La hora «Desde» no es válida.',
            'disponibilidad.*.hasta.date_format' => 'La hora «Hasta» no es válida.',
        ];
    }
}
