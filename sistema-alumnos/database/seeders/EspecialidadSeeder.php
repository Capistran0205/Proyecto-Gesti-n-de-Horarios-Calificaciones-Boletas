<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use Illuminate\Database\Seeder;

class EspecialidadSeeder extends Seeder
{
    // Se puede ejecutar más de una vez sin duplicar registros
    public function run(): void
    {
        $especialidades = [
            'Laboratorista clínico',
            'Informática / Tecnologías de la información',
            'Dibujo',
        ];

        foreach ($especialidades as $descripcion) {
            Especialidad::firstOrCreate(['descripcion' => $descripcion]);
        }
    }
}
