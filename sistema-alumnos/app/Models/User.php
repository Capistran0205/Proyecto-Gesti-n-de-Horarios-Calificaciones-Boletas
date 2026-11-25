<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const ROL_SUPERUSUARIO = 0;
    public const ROL_ADMINISTRADOR = 1;
    public const ROL_DOCENTE = 2;
    public const ROL_ALUMNO = 3;

    // Nombre y apellidos: solo letras (con acentos) separadas por un espacio
    public const REGEX_NOMBRE_PERSONA = '/^[\p{L}\p{M}]+(?: [\p{L}\p{M}]+)*$/u';

    public const ESTADO_BAJA = 0;
    public const ESTADO_ACTIVO = 1;

    public const ROLES = [
        self::ROL_SUPERUSUARIO => 'SuperUsuario',
        self::ROL_ADMINISTRADOR => 'Administrador',
        self::ROL_DOCENTE => 'Docente',
        self::ROL_ALUMNO => 'Alumno',
    ];

    private const RUTAS_PANEL = [
        0 => 'panel.superusuario',
        1 => 'panel.administrador',
        2 => 'panel.docente',
        3 => 'panel.alumno',
    ];

    protected $table = 'usuario';

    const CREATED_AT = 'fecha_registro';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'nombre', 'apellido_pat', 'apellido_mat', 'rol',
        'usuario', 'correo_electronico', 'contrasena', 'estado', 'cambio_contrasena_ini',
    ];

    protected $hidden = ['contrasena'];

    protected function casts(): array
    {
        return [
            'contrasena' => 'hashed',
            'estado' => 'integer',
            'cambio_contrasena_ini' => 'boolean',
        ];
    }

    public function nombreRol(): string
    {
        return self::ROLES[$this->rol] ?? 'Sin rol';
    }

    public function rutaPanel(): string
    {
        return self::RUTAS_PANEL[$this->rol];
    }

    public function nombreCompleto(): string
    {
        return trim("{$this->nombre} {$this->apellido_pat} {$this->apellido_mat}");
    }

    // Permite usar $usuario->nombre_completo
    public function getNombreCompletoAttribute(): string
    {
        return $this->nombreCompleto();
    }

    public function administrador(): HasOne
    {
        return $this->hasOne(Administrador::class, 'id_admin');
    }

    public function docente(): HasOne
    {
        return $this->hasOne(Docente::class, 'id_docente');
    }

    public function alumno(): HasOne
    {
        return $this->hasOne(Alumno::class, 'id_alumno');
    }

    public function scopeAdministradores(Builder $consulta): Builder
    {
        return $consulta->where('rol', self::ROL_ADMINISTRADOR);
    }

    public function scopeDocentes(Builder $consulta): Builder
    {
        return $consulta->where('rol', self::ROL_DOCENTE);
    }

    public function scopeAlumnos(Builder $consulta): Builder
    {
        return $consulta->where('rol', self::ROL_ALUMNO);
    }

    public function scopeActivos(Builder $consulta): Builder
    {
        return $consulta->where('estado', self::ESTADO_ACTIVO);
    }

    // Sin caracteres ambiguos (0 O o 1 l I). Se guarda hasheada por el cast 'hashed'.
    public static function generarContrasenaTemporal(int $longitud = 10): string
    {
        $caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $maximo = strlen($caracteres) - 1;
        $contrasena = '';

        for ($i = 0; $i < max($longitud, 8); $i++) {
            $contrasena .= $caracteres[random_int(0, $maximo)];
        }

        return $contrasena;
    }

    public function getAuthPassword()
    {
        return $this->contrasena;
    }

    public function getAuthPasswordName()
    {
        return 'contrasena';
    }

    public function getEmailForPasswordReset()
    {
        return $this->correo_electronico;
    }

    public function routeNotificationForMail()
    {
        return $this->correo_electronico;
    }

    public function getRememberTokenName()
    {
        return '';
    }
}