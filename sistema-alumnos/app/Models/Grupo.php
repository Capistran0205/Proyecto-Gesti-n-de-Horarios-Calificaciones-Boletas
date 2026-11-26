<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Grupo = periodo + semestre + letra (único por periodo). Los de 5.º y 6.º llevan especialidad.
 * El administrador crea grupos con capacidad de 40; solo el SuperUsuario la modifica (HU-06).
 * La relación horario() se agrega en el módulo de horarios, cuando exista HorarioGrupo.
 */
class Grupo extends Model
{
    // Tope con el que el administrador crea cada grupo
    public const CAPACIDAD_INICIAL = 40;

    // capacidad_max es SMALLINT: ese es el máximo real que admite la BD
    public const CAPACIDAD_MAXIMA_BD = 32767;

    protected $table = 'grupo';

    const CREATED_AT = 'fecha_registro';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = ['id_especialidad', 'id_periodo', 'semestre', 'letra', 'capacidad_max'];

    protected function casts(): array
    {
        return [
            'id_especialidad' => 'integer',
            'id_periodo' => 'integer',
            'semestre' => 'integer',
            'capacidad_max' => 'integer',
        ];
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoEscolar::class, 'id_periodo');
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad');
    }

    // Alumnos inscritos, vía detalle_grupo (quitar a uno borra solo esa fila)
    public function alumnos(): BelongsToMany
    {
        return $this->belongsToMany(Alumno::class, 'detalle_grupo', 'id_grupo', 'id_alumno');
    }

    public function scopeDelPeriodo(Builder $consulta, int $idPeriodo): Builder
    {
        return $consulta->where('id_periodo', $idPeriodo);
    }

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderBy('semestre')->orderBy('letra');
    }

    // Letras permitidas (CHECK grupo_letra_check): A–Z
    public static function letras(): array
    {
        return range('A', 'Z');
    }

    // Nombre en pantalla: "5°A" (misma forma que Alumno::nombreGrupo)
    public function getNombreGrupoAttribute(): string
    {
        return Alumno::nombreGrupo($this->semestre, $this->letra);
    }

    // 5.º y 6.º son grupos de especialidad (CHECK ck_grupo_especialidad)
    public function esDeEspecialidad(): bool
    {
        return Alumno::semestreRequiereEspecialidad($this->semestre);
    }

    // Usa alumnos_count si el grupo se cargó con withCount('alumnos'); si no, cuenta en la BD
    public function totalInscritos(): int
    {
        return (int) ($this->alumnos_count ?? $this->alumnos()->count());
    }

    public function lugaresDisponibles(): int
    {
        return max(0, $this->capacidad_max - $this->totalInscritos());
    }

    public function estaLleno(): bool
    {
        return $this->lugaresDisponibles() === 0;
    }

    // Solo se asigna o quita en el periodo vigente o en uno futuro: alumno.semestre es el semestre actual, no el histórico
    public function permiteAsignarAlumnos(): bool
    {
        return $this->periodo->fecha_fin->toDateString() >= now()->toDateString();
    }

    // Usuarios inscritos en el grupo, por apellido (para el detalle del grupo)
    public function usuariosInscritos(): Builder
    {
        return User::alumnos()
            ->with('alumno')
            ->whereIn('id', fn ($q) => $q->select('id_alumno')->from('detalle_grupo')->where('id_grupo', $this->id))
            ->orderBy('apellido_pat')
            ->orderBy('apellido_mat')
            ->orderBy('nombre');
    }

    // Alumnos que se pueden asignar: activos, del mismo semestre (y especialidad en 5.º y 6.º)
    // y sin grupo en el periodo de este grupo
    public function alumnosElegibles(): Builder
    {
        return User::alumnos()
            ->activos()
            ->with('alumno.especialidad')
            ->whereHas('alumno', function ($q) {
                $q->where('semestre', $this->semestre);

                if ($this->esDeEspecialidad()) {
                    $q->where('id_especialidad', $this->id_especialidad);
                }
            })
            ->whereNotIn('id', fn ($q) => $q->select('d.id_alumno')
                ->from('detalle_grupo as d')
                ->join('grupo as g', 'g.id', '=', 'd.id_grupo')
                ->where('g.id_periodo', $this->id_periodo))
            ->orderBy('apellido_pat')
            ->orderBy('apellido_mat')
            ->orderBy('nombre');
    }
}