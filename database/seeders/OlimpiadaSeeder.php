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
            'fecha_inicio' => Carbon::create('2025', '01', '15'),
            'fecha_fin' => Carbon::create('2025', '01', '20'),
        ]);

        Olimpiada::create([
            'nombre' => 'Oh!Sansi 2025 - II',
            'gestion' => '2025 - II',
            'fecha_inicio' => Carbon::create('2025', '06', '15'),
            'fecha_fin' => Carbon::create('2025', '06', '20'),
        ]);

        Olimpiada::create([
            'nombre' => 'Oh!Sansi 2026 - I',
            'gestion' => '2026 - I',
            'fecha_inicio' => Carbon::create('2026', '01', '10'),
            'fecha_fin' => Carbon::create('2026', '01', '15'),
        ]);
    }
}
