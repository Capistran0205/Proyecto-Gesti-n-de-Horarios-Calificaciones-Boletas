<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Alumno extends Model
{
    // Mismas expresiones que los CHECK de esquema_preparatoria.sql (CURP: una sola definición, la del administrador)
    public const REGEX_CURP = Administrador::REGEX_CURP;
    public const REGEX_NSS = '/^[0-9]{11}$/';

    public const SEMESTRE_MINIMO = 1;
    public const SEMESTRE_MAXIMO = 6;
    // Desde este semestre el alumno cursa una especialidad
    public const SEMESTRE_INICIO_ESPECIALIDAD = 5;

    public const ANIO_INGRESO_MINIMO = 2000;
    public const ANIO_INGRESO_MAXIMO = 2100;

    protected $table = 'alumno';

    protected $primaryKey = 'id_alumno';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['id_alumno', 'id_especialidad', 'curp', 'nss', 'anio_ingreso', 'semestre'];

    protected function casts(): array
    {
        return [
            'id_especialidad' => 'integer',
            'anio_ingreso' => 'integer',
            'semestre' => 'integer',
        ];
    }

    // Las relaciones grupos() y calificaciones() se agregan en los módulos de grupos y calificaciones,
    // cuando existan los modelos Grupo y CalificacionAlumno.

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_alumno');
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad');
    }

    // Grupos: se leen directo de las tablas grupo y detalle_grupo (el modelo Grupo llega con el módulo de grupos).
    // "Vigente" = el periodo que devuelve PeriodoEscolar::vigente().

    // Nombre en pantalla del grupo: "3°E"
    public static function nombreGrupo(int $semestre, string $letra): string
    {
        return "{$semestre}°{$letra}";
    }

    // Grupos del periodo vigente, para el filtro del listado: objetos {id, nombre}
    public static function gruposDelPeriodoVigente(): Collection
    {
        $idPeriodo = PeriodoEscolar::vigente()?->id;

        if ($idPeriodo === null) {
            return collect();
        }

        return DB::table('grupo')
            ->where('id_periodo', $idPeriodo)
            ->orderBy('semestre')
            ->orderBy('letra')
            ->get(['id', 'semestre', 'letra'])
            ->map(fn ($grupo) => (object) ['id' => $grupo->id, 'nombre' => self::nombreGrupo($grupo->semestre, $grupo->letra)]);
    }

    // Grupo vigente de varios alumnos en una sola consulta: [id_alumno => {id, nombre}]. Quien no aparece no tiene grupo.
    public static function gruposVigentesDe(array $idsAlumno): Collection
    {
        $idPeriodo = PeriodoEscolar::vigente()?->id;

        if ($idPeriodo === null || count($idsAlumno) === 0) {
            return collect();
        }

        return DB::table('detalle_grupo as d')
            ->join('grupo as g', 'g.id', '=', 'd.id_grupo')
            ->where('g.id_periodo', $idPeriodo)
            ->whereIn('d.id_alumno', $idsAlumno)
            ->get(['d.id_alumno', 'g.id', 'g.semestre', 'g.letra'])
            ->mapWithKeys(fn ($fila) => [
                $fila->id_alumno => (object) ['id' => $fila->id, 'nombre' => self::nombreGrupo($fila->semestre, $fila->letra)],
            ]);
    }

    // Grupo del alumno en el periodo vigente ({id, nombre}) o null si no tiene
    public function grupoVigente(): ?object
    {
        return self::gruposVigentesDe([$this->id_alumno])->get($this->id_alumno);
    }

    // Lo saca del grupo del periodo vigente (borra solo esa asignación; periodos anteriores y calificaciones se conservan)
    public function salirDelGrupoVigente(): int
    {
        $idPeriodo = PeriodoEscolar::vigente()?->id;

        if ($idPeriodo === null) {
            return 0;
        }

        return DB::table('detalle_grupo')
            ->where('id_alumno', $this->id_alumno)
            ->whereIn('id_grupo', fn ($consulta) => $consulta->select('id')->from('grupo')->where('id_periodo', $idPeriodo))
            ->delete();
    }

    // En 5.º y 6.º la especialidad es obligatoria; en 1.º a 4.º no existe (CHECK ck_alumno_especialidad)
    public static function semestreRequiereEspecialidad(int $semestre): bool
    {
        return $semestre >= self::SEMESTRE_INICIO_ESPECIALIDAD;
    }

    // Etiqueta legible: "3.º semestre"
    public function getSemestreTextoAttribute(): string
    {
        return "{$this->semestre}.º semestre";
    }
}
