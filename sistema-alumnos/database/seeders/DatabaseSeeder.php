<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    // Usuarios de prueba, uno por rol. Contraseña de todos: Prueba12345
    public function run(): void
    {
        // Catálogos primero: el docente de prueba necesita un área disciplinar
        $this->call([
            EspecialidadSeeder::class,
            AreaDisciplinarSeeder::class,
        ]);

        DB::transaction(function () {
            $super = $this->crearUsuario('superprueba', 'Super', User::ROL_SUPERUSUARIO);
            DB::table('super_usuario')->insertOrIgnore([
                'id_super_usuario' => $super->id,
            ]);

            $admin = $this->crearUsuario('adminprueba', 'Admin', User::ROL_ADMINISTRADOR);
            DB::table('administrador')->insertOrIgnore([
                'id_admin' => $admin->id,
                'curp' => 'XEXX010101HVZXXX09',
                'rfc' => 'XEXX010101AB1',
            ]);

            // El docente necesita un área disciplinar (NOT NULL en el esquema)
            $idArea = DB::table('area_disciplinar')->where('descripcion', 'Pensamiento Matemático')->value('id');

            $docente = $this->crearUsuario('docente1001', 'Docente', User::ROL_DOCENTE);
            DB::table('docente')->insertOrIgnore([
                'id_docente' => $docente->id,
                'id_area_disciplinar' => $idArea,
                'curp' => 'XEXX010102HVZXXX09',
                'rfc' => 'XEXX010102AB1',
                'horas_plaza' => 20,
            ]);

            $alumno = $this->crearUsuario('alumno20260001', 'Alumno', User::ROL_ALUMNO);
            DB::table('alumno')->insertOrIgnore([
                'id_alumno' => $alumno->id,
                'curp' => 'XEXX010103HVZXXX09',
                'anio_ingreso' => 2026,
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
                'correo_electronico' => $usuario . '@example.com',
                'contrasena' => 'Prueba12345',
                'cambio_contrasena_ini' => false, // cuentas de prueba: entran directo
            ]
        );
    }
}
