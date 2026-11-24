<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Especialidad que el alumno cursa desde 5.º semestre
class Especialidad extends Model
{
    protected $table = 'especialidad';

    const CREATED_AT = 'fecha_registro';
    const UPDATED_AT = null;

    protected $fillable = ['descripcion'];
}
