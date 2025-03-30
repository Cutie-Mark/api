<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ColegioSeeder extends Seeder
{
    public function run()
    {
        $colegios = [
            ['nombre' => 'Instituto Americano'],
            ['nombre' => 'Colegio San Ignacio'],
            ['nombre' => 'Colegio Irlandes'],
            ['nombre' => 'Colegio San José'],
            ['nombre' => 'Colegio Bolívar'],
            ['nombre' => 'Colegio San Agustin'],
            ['nombre' => 'Colegio La Salle'],
            ['nombre' => 'Colegio Alemán'],
            ['nombre' => 'Colegio San Juan'],
            ['nombre' => 'Colegio Don Bosco'],
        ];

        DB::table('colegios')->insert($colegios);
    }
}
