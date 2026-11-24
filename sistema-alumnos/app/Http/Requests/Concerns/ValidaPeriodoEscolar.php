<?php

namespace App\Http\Requests\Concerns;

use App\Models\PeriodoEscolar;
use Illuminate\Contracts\Validation\Validator;

/** Reglas y mensajes compartidos por el alta y la edición de periodos escolares. */
trait ValidaPeriodoEscolar
{
    // Nombre con el formato "Mes Año - Mes Año": guion largo → guion, espacios normalizados, mes con mayúscula inicial
    protected function normalizarNombrePeriodo(): void
    {
        $nombre = trim((string) $this->input('nombre'));
        $nombre = preg_replace('/\s*[-–—]\s*/u', ' - ', $nombre);
        $nombre = preg_replace('/\s+/u', ' ', $nombre);

        $this->merge([
            'nombre' => mb_convert_case(mb_strtolower($nombre), MB_CASE_TITLE),
            'fecha_inicio' => trim((string) $this->input('fecha_inicio')),
            'fecha_fin' => trim((string) $this->input('fecha_fin')),
        ]);
    }

    // Un periodo no puede compartir ningún día con otro; $idActual excluye al que se edita
    protected function validarTraslape(Validator $validador, ?int $idActual): void
    {
        $validador->after(function (Validator $validador) use ($idActual) {
            if ($validador->errors()->hasAny(['fecha_inicio', 'fecha_fin'])) {
                return;
            }

            $datos = $validador->getData();
            $otro = PeriodoEscolar::traslapados($datos['fecha_inicio'], $datos['fecha_fin'])
                ->when($idActual, fn ($consulta) => $consulta->where('id', '!=', $idActual))
                ->first();

            if ($otro) {
                $validador->errors()->add('fecha_inicio', "Las fechas se traslapan con el periodo {$otro->nombre} ({$otro->fecha_inicio->format('d/m/Y')} al {$otro->fecha_fin->format('d/m/Y')}).");
            }
        });
    }

    protected function mensajesPeriodo(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre del periodo.',
            'nombre.max' => 'El nombre no puede pasar de 40 caracteres.',
            'nombre.regex' => 'El nombre debe tener el formato "Mes Año - Mes Año", por ejemplo: Enero 2026 - Junio 2026.',
            'nombre.unique' => 'Ya existe un periodo con ese nombre.',
            'fecha_inicio.required' => 'Selecciona la fecha de inicio.',
            'fecha_inicio.date_format' => 'La fecha de inicio no es válida.',
            'fecha_fin.required' => 'Selecciona la fecha de fin.',
            'fecha_fin.date_format' => 'La fecha de fin no es válida.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ];
    }
}
