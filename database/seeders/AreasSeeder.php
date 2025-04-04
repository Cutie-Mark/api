<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;

class AreasSeeder extends Seeder
{
    /**
     * Ejecuta las semillas de la base de datos.
     */
    public function run(): void
    {
        // Datos estáticos de las áreas
        $areas = [
            ['nombre' => 'ASTRONOMIA - ASTROFISICA'],
            ['nombre' => 'BIOLOGIA'],
            ['nombre' => 'FISICA'],
            ['nombre' => 'INFORMATICA'],
            ['nombre' => 'MATEMATICAS'],
            ['nombre' => 'QUIMICA'],
            ['nombre' => 'ROBOTICA'],
        ];

        // Insertar las áreas en la base de datos
        foreach ($areas as $areaData) {
            $area = Area::create($areaData);
            $area->olimpiadas()->attach(1); // Relación con olimpiada_id = 1
        }
    }
}
