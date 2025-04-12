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
            ['nombre' => 'ASTRONOMIA - ASTROFISICA', 'vigente' => true],
            ['nombre' => 'BIOLOGIA', 'vigente' => true],
            ['nombre' => 'FISICA', 'vigente' => true],
            ['nombre' => 'INFORMATICA', 'vigente' => true],
            ['nombre' => 'MATEMATICAS', 'vigente' => true],
            ['nombre' => 'QUIMICA', 'vigente' => true],
            ['nombre' => 'ROBOTICA', 'vigente' => true],
        ];

        // Insertar las áreas en la base de datos
        foreach ($areas as $areaData) {
            $area = Area::create($areaData);
   
        }
    }
}
