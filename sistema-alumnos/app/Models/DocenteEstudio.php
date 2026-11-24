<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocenteEstudio extends Model
{
    public const GRADO_TSU = 1;
    public const GRADO_LICENCIATURA = 2;
    public const GRADO_ESPECIALIDAD = 3;
    public const GRADO_MAESTRIA = 4;
    public const GRADO_DOCTORADO = 5;

    public const GRADOS = [
        self::GRADO_TSU => 'TSU',
        self::GRADO_LICENCIATURA => 'Licenciatura',
        self::GRADO_ESPECIALIDAD => 'Especialidad',
        self::GRADO_MAESTRIA => 'Maestría',
        self::GRADO_DOCTORADO => 'Doctorado',
    ];

    protected $table = 'docente_estudio';

    public $timestamps = false;

    protected $fillable = ['id_docente', 'descripcion', 'grado_estudio'];

    protected function casts(): array
    {
        return ['grado_estudio' => 'integer'];
    }

    // Permite usar $estudio->grado_texto
    public function getGradoTextoAttribute(): string
    {
        return self::GRADOS[$this->grado_estudio] ?? 'Sin grado';
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }
}
