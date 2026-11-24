<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Administrador extends Model
{
    // Mismas expresiones que los CHECK de esquema_preparatoria.sql
    public const REGEX_CURP = '/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9A-Z][0-9]$/';
    public const REGEX_RFC = '/^[A-ZÑ&]{4}[0-9]{6}[A-Z0-9]{3}$/u';

    protected $table = 'administrador';

    protected $primaryKey = 'id_admin';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['id_admin', 'curp', 'rfc'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_admin');
    }
}
