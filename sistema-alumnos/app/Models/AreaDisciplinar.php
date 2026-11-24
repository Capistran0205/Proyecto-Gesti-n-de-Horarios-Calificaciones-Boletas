<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Área a la que pertenece cada materia y de la que se derivan las áreas del docente
class AreaDisciplinar extends Model
{
    protected $table = 'area_disciplinar';

    const CREATED_AT = 'fecha_registro';
    const UPDATED_AT = null;

    protected $fillable = ['descripcion'];
}
