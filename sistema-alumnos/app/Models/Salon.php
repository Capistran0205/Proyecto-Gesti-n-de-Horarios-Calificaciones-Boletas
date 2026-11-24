<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Salon extends Model
{
    public const TIPO_NORMAL = 1;
    public const TIPO_ESPECIAL = 2; // laboratorio, taller, cómputo...

    public const TIPOS = [
        self::TIPO_NORMAL => 'Aula normal',
        self::TIPO_ESPECIAL => 'Aula especial',
    ];

    protected $table = 'salon';

    const CREATED_AT = 'fecha_registro';
    const UPDATED_AT = null;

    protected $fillable = ['descripcion', 'edificio', 'tipo'];

    protected function casts(): array
    {
        return ['tipo' => 'integer'];
    }

    // Permite usar $salon->tipo_texto
    public function getTipoTextoAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? 'Sin tipo';
    }

    public function scopeBuscar(Builder $consulta, string $texto): Builder
    {
        return $consulta->where(fn ($q) => $q
            ->where('descripcion', 'ilike', "%{$texto}%")
            ->orWhere('edificio', 'ilike', "%{$texto}%"));
    }
}
