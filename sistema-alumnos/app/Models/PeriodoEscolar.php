<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PeriodoEscolar extends Model
{
    public const MESES = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
    ];

    protected $table = 'periodo_escolar';

    public $timestamps = false;

    protected $fillable = ['nombre', 'fecha_inicio', 'fecha_fin'];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    // Formato del nombre: "Mes Año - Mes Año" (p. ej. Enero 2026 - Junio 2026)
    public static function regexNombre(): string
    {
        $meses = implode('|', self::MESES);

        return "/^(?:{$meses}) [0-9]{4} - (?:{$meses}) [0-9]{4}$/";
    }

    // El periodo vigente es el que contiene la fecha actual; si no hay, el de inicio más reciente
    public static function vigente(): ?self
    {
        $hoy = now()->toDateString();

        return self::where('fecha_inicio', '<=', $hoy)
            ->where('fecha_fin', '>=', $hoy)
            ->first()
            ?? self::orderByDesc('fecha_inicio')->first();
    }

    // Periodos que comparten al menos un día con el rango dado (extremos incluidos)
    public function scopeTraslapados(Builder $consulta, string $inicio, string $fin): Builder
    {
        return $consulta->where('fecha_inicio', '<=', $fin)->where('fecha_fin', '>=', $inicio);
    }
}
