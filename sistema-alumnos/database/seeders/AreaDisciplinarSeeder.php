<?php

namespace Database\Seeders;

use App\Models\AreaDisciplinar;
use Illuminate\Database\Seeder;

class AreaDisciplinarSeeder extends Seeder
{
    // Se puede ejecutar más de una vez sin duplicar registros
    public function run(): void
    {
        $areas = [
            'Ciencias Naturales',
            'Pensamiento Matemático',
            'Humanidades',
            'Ciencias Sociales',
            'Lengua y Comunicación',
            'Inglés',
            'Formación para el Trabajo',
        ];

        foreach ($areas as $descripcion) {
            AreaDisciplinar::firstOrCreate(['descripcion' => $descripcion]);
        }
    }
}
