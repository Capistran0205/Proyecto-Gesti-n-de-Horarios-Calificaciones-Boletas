<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Docente extends Model
{
    // Mismas expresiones que los CHECK del esquema (una sola definición, la del administrador)
    public const REGEX_CURP = Administrador::REGEX_CURP;
    public const REGEX_RFC = Administrador::REGEX_RFC;

    public const HORAS_TIEMPO_COMPLETO = 40;
    public const HORAS_PLAZA_MINIMA = 2;

    protected $table = 'docente';

    protected $primaryKey = 'id_docente';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['id_docente', 'id_area_disciplinar', 'curp', 'rfc', 'disponibilidad', 'horas_plaza'];

    protected function casts(): array
    {
        return [
            'id_area_disciplinar' => 'integer',
            'horas_plaza' => 'integer',
            'disponibilidad' => 'array',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_docente');
    }

    public function areaDisciplinar(): BelongsTo
    {
        return $this->belongsTo(AreaDisciplinar::class, 'id_area_disciplinar');
    }

    public function estudios(): HasMany
    {
        return $this->hasMany(DocenteEstudio::class, 'id_docente', 'id_docente');
    }

    // Materias que el docente puede impartir. Se cargan directamente en la base (docente_materia);
    // la aplicación solo las lee.
    public function materias(): BelongsToMany
    {
        return $this->belongsToMany(Materia::class, 'docente_materia', 'id_docente', 'id_materia');
    }

    // La relación clases() se agrega en el módulo de horarios, cuando exista DetalleHorarioGrupo

    public function esTiempoCompleto(): bool
    {
        return $this->horas_plaza === self::HORAS_TIEMPO_COMPLETO;
    }

    // Disponibilidad vacía con las cinco claves (1 lunes ... 5 viernes), como se guarda siempre
    public static function disponibilidadVacia(): array
    {
        return array_fill_keys(array_keys(config('horario.dias')), []);
    }

    // Una disponibilidad NULL (docente sin captura) equivale a no tener ningún bloque disponible
    public function estaDisponible(int $dia, int $bloque): bool
    {
        $bloques = $this->disponibilidad[(string) $dia] ?? [];

        return in_array($bloque, $bloques, true);
    }

    // Bloques de clase (1-7) que quedan completamente dentro del rango "Desde-Hasta" (p. ej. "15:00" a "21:20")
    public static function bloquesDelRango(string $desde, string $hasta): array
    {
        $bloques = [];

        foreach (config('horario.bloques') as $numero => $bloque) {
            if ($bloque['inicio'] >= $desde && $bloque['fin'] <= $hasta) {
                $bloques[] = $numero;
            }
        }

        return $bloques;
    }

    // Rango que cubre una lista de bloques: del inicio del primero al fin del último
    public static function rangoDeBloques(array $bloques): ?array
    {
        if (count($bloques) === 0) {
            return null;
        }

        $configuracion = config('horario.bloques');

        return [
            'desde' => $configuracion[min($bloques)]['inicio'],
            'hasta' => $configuracion[max($bloques)]['fin'],
        ];
    }

    // Para los formularios: por cada día, si labora y su rango. Un día sin bloques muestra el horario escolar completo.
    public function rangosPorDia(): array
    {
        $configuracion = config('horario.bloques');
        $rangos = [];

        foreach (array_keys(config('horario.dias')) as $dia) {
            $rango = self::rangoDeBloques(array_map('intval', $this->disponibilidad[(string) $dia] ?? []));

            $rangos[$dia] = [
                'activo' => $rango !== null,
                'desde' => $rango['desde'] ?? $configuracion[array_key_first($configuracion)]['inicio'],
                'hasta' => $rango['hasta'] ?? $configuracion[array_key_last($configuracion)]['fin'],
            ];
        }

        return $rangos;
    }

    // Texto corto de la disponibilidad: "Lun a Vie · 15:00 a 21:20" (o "Aún sin definir")
    public function resumenDisponibilidad(): string
    {
        $grupos = [];

        foreach ($this->rangosPorDia() as $dia => $rango) {
            if ($rango['activo']) {
                $grupos["{$rango['desde']} a {$rango['hasta']}"][] = $dia;
            }
        }

        if (count($grupos) === 0) {
            return 'Aún sin definir';
        }

        $partes = [];
        foreach ($grupos as $horario => $dias) {
            $partes[] = self::textoDeDias($dias) . ' · ' . $horario;
        }

        return implode(' | ', $partes);
    }

    private static function textoDeDias(array $dias): string
    {
        $cortos = config('horario.dias_cortos');
        $primero = $cortos[$dias[0]];
        $ultimo = $cortos[end($dias)];

        if (count($dias) >= 3 && end($dias) - $dias[0] + 1 === count($dias)) {
            return "{$primero} a {$ultimo}"; // días seguidos
        }

        if (count($dias) === 2) {
            return "{$primero} y {$ultimo}";
        }

        return implode(', ', array_map(fn ($dia) => $cortos[$dia], $dias));
    }

    // ¿Tiene clases en el horario de ese periodo? (consulta directa: el modelo de clases llega con los horarios)
    public function tieneClasesEnPeriodo(?int $idPeriodo): bool
    {
        if ($idPeriodo === null) {
            return false;
        }

        return DB::table('detalle_horario_grupo as d')
            ->join('horario_grupo as h', 'h.id', '=', 'd.id_horario_grupo')
            ->join('grupo as g', 'g.id', '=', 'h.id_grupo')
            ->where('d.id_docente', $this->id_docente)
            ->where('g.id_periodo', $idPeriodo)
            ->exists();
    }
}
