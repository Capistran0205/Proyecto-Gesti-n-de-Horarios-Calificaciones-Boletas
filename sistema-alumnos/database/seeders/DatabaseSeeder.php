<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    // Roles: 0 SuperUsuario, 1 Administrador, 2 Docente, 3 Alumno
    public function run(): void
    {
        DB::transaction(function () {
            $super = $this->crearUsuario('superprueba', 'Super', 0);
            DB::table('superusuario')->insertOrIgnore([
                'id_superusuario' => $super->id,
            ]);

            $admin = $this->crearUsuario('adminprueba', 'Admin', 1);
            DB::table('administrador')->insertOrIgnore([
                'id_admin' => $admin->id,
            ]);

            $docente = $this->crearUsuario('docente1001', 'Docente', 2);
            DB::table('docente')->insertOrIgnore([
                'id_docente' => $docente->id,
                'curp' => 'XEXX010101HVZXXX09',
                'rfc' => 'XEXX010101AB1',
                'modalidad' => 1,
            ]);

            $alumno = $this->crearUsuario('alumno20260001', 'Alumno', 3);
            DB::table('alumno')->insertOrIgnore([
                'id_alumno' => $alumno->id,
                'curp' => 'XEXX010102HVZXXX09',
                'modalidad' => 1,
                'semestre' => 1,
            ]);
        });
    }

    private function crearUsuario(string $usuario, string $nombre, int $rol): User
    {
        return User::firstOrCreate(
            ['usuario' => $usuario],
            [
                'nombre' => $nombre,
                'apellido_pat' => 'Prueba',
                'rol' => $rol,
                'correo' => $usuario . '@example.com',
                'contrasena' => 'Prueba12345',
            ]
        );
    }
}