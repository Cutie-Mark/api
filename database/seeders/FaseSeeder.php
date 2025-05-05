<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Fase;

class FaseSeeder extends Seeder
{
    public function run(): void
    {
        Fase::create(['nombre_fase' => 'Preparación', 'orden' => 1]);
        Fase::create(['nombre_fase' => 'Lanzamiento', 'orden' => 2]);
        Fase::create(['nombre_fase' => 'Primera inscripción', 'orden' => 3]);
        Fase::create(['nombre_fase' => 'Segunda inscripción', 'orden' => 4]);
        Fase::create(['nombre_fase' => 'Tercera inscripción', 'orden' => 5]);
        Fase::create(['nombre_fase' => 'Cuarta inscripción', 'orden' => 6]);
        Fase::create(['nombre_fase' => 'Primera clasificación', 'orden' => 7]);
        Fase::create(['nombre_fase' => 'Segunda clasificación', 'orden' => 8]);
        Fase::create(['nombre_fase' => 'Tercera clasificación', 'orden' => 9]);
        Fase::create(['nombre_fase' => 'Final', 'orden' => 10]);
        Fase::create(['nombre_fase' => 'Segunda Final', 'orden' => 11]);
        Fase::create(['nombre_fase' => 'Premiación', 'orden' => 12]);
        Fase::create(['nombre_fase' => 'Segunda premiación', 'orden' => 13]);
    }
}
