<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Materia extends Model
{
    public const TIPO_TRONCO_COMUN = 1;
    public const TIPO_ESPECIALIDAD = 2;

    public const TIPOS = [
        self::TIPO_TRONCO_COMUN => 'Tronco común',
        self::TIPO_ESPECIALIDAD => 'Especialidad',
    ];

    public const ESTADO_BAJA = 0;
    public const ESTADO_ACTIVA = 1;

    public const ESTADOS = [
        self::ESTADO_BAJA => 'De baja',
        self::ESTADO_ACTIVA => 'Activa',
    ];

    protected $table = 'materia';

    const CREATED_AT = 'fecha_registro';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'id_especialidad', 'id_area_disciplinar', 'nombre', 'clave',
        'horas_semana', 'tipo', 'semestre', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'id_especialidad' => 'integer',
            'id_area_disciplinar' => 'integer',
            'horas_semana' => 'integer',
            'tipo' => 'integer',
            'semestre' => 'integer',
            'estado' => 'integer',
        ];
    }

    // La clave siempre se guarda en mayúsculas y sin espacios en los extremos
    protected function clave(): Attribute
    {
        return Attribute::make(
            set: fn (?string $valor) => $valor === null ? null : mb_strtoupper(trim($valor)),
        );
    }

    // Etiquetas legibles: $materia->tipo_texto y $materia->estado_texto
    public function getTipoTextoAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? 'Sin tipo';
    }

    public function getEstadoTextoAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? 'Sin estado';
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad');
    }

    public function areaDisciplinar(): BelongsTo
    {
        return $this->belongsTo(AreaDisciplinar::class, 'id_area_disciplinar');
    }

    // La relación docentes() se agrega en el módulo de docentes, cuando exista el modelo Docente

    // Solo las materias que se pueden asignar a docentes y horarios (las de baja quedan fuera)
    public function scopeActivas(Builder $consulta): Builder
    {
        return $consulta->where('estado', self::ESTADO_ACTIVA);
    }

    public function scopeDelSemestre(Builder $consulta, int $semestre): Builder
    {
        return $consulta->where('semestre', $semestre);
    }

    // Materias de un grupo: tronco común del semestre + las de la especialidad del grupo (5.º y 6.º).
    // No filtra por estado: combínalo con activas() donde corresponda.
    public function scopeParaGrupo(Builder $consulta, int $semestre, ?int $idEspecialidad): Builder
    {
        return $consulta->where('semestre', $semestre)->where(function (Builder $q) use ($idEspecialidad) {
            $q->where('tipo', self::TIPO_TRONCO_COMUN);

            if ($idEspecialidad) {
                $q->orWhere(fn (Builder $e) => $e
                    ->where('tipo', self::TIPO_ESPECIALIDAD)
                    ->where('id_especialidad', $idEspecialidad));
            }
        });
    }
}
