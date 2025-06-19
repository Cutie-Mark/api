<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Olimpiada;
use Carbon\Carbon;

class OlimpiadaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Crear 3 olimpiadas con datos de ejemplo y fechas más espaciadas
        Olimpiada::create([
            'nombre' => 'Oh!Sansi 2025 - I',
            'gestion' => '2025 - I',
            'fecha_inicio' => Carbon::create('2025', '02', '01'),
            'fecha_fin' => Carbon::create('2025', '05', '30'),
            'precio_inscripcion' => 16,
            'limite_inscripciones' => 2,
            'descripcion_convocatoria' => 'Olimpiada de San Simón del primer semestre 2025'
        ]);

        Olimpiada::create([
            'nombre' => 'Oh!Sansi 2025 - II',
            'gestion' => '2025 - II',
            'fecha_inicio' => Carbon::create('2025', '07', '01'),
            'fecha_fin' => Carbon::create('2025', '10', '30'),
            'precio_inscripcion' => 16,
            'limite_inscripciones' => 3,
            'descripcion_convocatoria' => 'Olimpiada de San Simón del segundo semestre 2025'
        ]);

        Olimpiada::create([
            'nombre' => 'Oh!Sansi 2026 - I',
            'gestion' => '2026 - I',
            'fecha_inicio' => Carbon::create('2026', '02', '01'),
            'fecha_fin' => Carbon::create('2026', '05', '30'),
            'precio_inscripcion' => 16,
            'limite_inscripciones' => 5,
            'descripcion_convocatoria' => 'Olimpiada de San Simón del primer semestre 2026'
        ]);
    }
}
