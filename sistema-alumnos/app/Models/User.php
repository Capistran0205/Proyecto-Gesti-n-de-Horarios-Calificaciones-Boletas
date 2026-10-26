<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const ROLES = [0 => 'SuperUsuario', 1 => 'Administrador', 2 => 'Docente', 3 => 'Alumno'];

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
        'usuario', 'correo', 'contrasena', 'estado',
    ];

    protected $hidden = ['contrasena'];

    protected function casts(): array
    {
        return [
            'contrasena' => 'hashed',
            'estado' => 'integer',
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
        return $this->correo;
    }

    public function routeNotificationForMail()
    {
        return $this->correo;
    }

    public function getRememberTokenName()
    {
        return '';
    }
}