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
        // Crear 3 olimpiadas con datos de ejemplo
        Olimpiada::create([
            'nombre' => 'Oh!Sansi 2025 - I',
            'gestion' => '2025 - I',
            'fecha_inicio' => Carbon::create('2025', '04', '15'),
            'fecha_fin' => Carbon::create('2025', '05', '20'),
            'precio_inscripcion' => 16,
            'limite_inscripciones' => 2,
            'descripcion_convocatoria' => ' .'
        ]);

        Olimpiada::create([
            'nombre' => 'Oh!Sansi 2025 - II',
            'gestion' => '2025 - II',
            'fecha_inicio' => Carbon::create('2025', '07', '15'),
            'fecha_fin' => Carbon::create('2025', '08', '20'),
            'precio_inscripcion' => 16,
            'limite_inscripciones' => 2,
            'descripcion_convocatoria' => '.'
        ]);

        Olimpiada::create([
            'nombre' => 'Oh!Sansi 2026 - I',
            'gestion' => '2026 - I',
            'fecha_inicio' => Carbon::create('2026', '04', '10'),
            'fecha_fin' => Carbon::create('2026', '05', '15'),
            'precio_inscripcion' => 16,
            'limite_inscripciones' => 2,
            'descripcion_convocatoria' => ' .'
        ]);
    }
}
