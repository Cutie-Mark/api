<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreaCategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // ASTRONOMIA - ASTROFISICA (3P-6P, 2S-6S)
            ['area_id' => 1, 'categoria_id' => 1],  // 3P
            ['area_id' => 1, 'categoria_id' => 2],  // 4P
            ['area_id' => 1, 'categoria_id' => 3],  // 5P
            ['area_id' => 1, 'categoria_id' => 4],  // 6P
            ['area_id' => 1, 'categoria_id' => 6],  // 2S (ID 6)
            ['area_id' => 1, 'categoria_id' => 7],  // 3S
            ['area_id' => 1, 'categoria_id' => 8],  // 4S
            ['area_id' => 1, 'categoria_id' => 9],  // 5S
            ['area_id' => 1, 'categoria_id' => 10], // 6S

            // BIOLOGIA (2S-6S)
            ['area_id' => 2, 'categoria_id' => 6],  // 2S
            ['area_id' => 2, 'categoria_id' => 7],  // 3S
            ['area_id' => 2, 'categoria_id' => 8],  // 4S
            ['area_id' => 2, 'categoria_id' => 9],  // 5S
            ['area_id' => 2, 'categoria_id' => 10], // 6S

            // FISICA (4S-6S)
            ['area_id' => 3, 'categoria_id' => 8],  // 4S
            ['area_id' => 3, 'categoria_id' => 9],  // 5S
            ['area_id' => 3, 'categoria_id' => 10], // 6S

            // INFORMATICA (Guacamayo, Guanaco, Londra, Jucumari, Bufeo, Puma)
            ['area_id' => 4, 'categoria_id' => 11], // Guacamayo
            ['area_id' => 4, 'categoria_id' => 12], // Guanaco
            ['area_id' => 4, 'categoria_id' => 13], // Londra
            ['area_id' => 4, 'categoria_id' => 14], // Jucumari
            ['area_id' => 4, 'categoria_id' => 15], // Bufeo
            ['area_id' => 4, 'categoria_id' => 16], // Puma

            // MATEMATICAS (Primer Nivel - Sexto Nivel)
            ['area_id' => 5, 'categoria_id' => 17], // Primer Nivel
            ['area_id' => 5, 'categoria_id' => 18], // Segundo Nivel
            ['area_id' => 5, 'categoria_id' => 19], // Tercer Nivel
            ['area_id' => 5, 'categoria_id' => 20], // Cuarto Nivel
            ['area_id' => 5, 'categoria_id' => 21], // Quinto Nivel
            ['area_id' => 5, 'categoria_id' => 22], // Sexto Nivel

            // QUIMICA (2S-6S)
            ['area_id' => 6, 'categoria_id' => 6],  // 2S
            ['area_id' => 6, 'categoria_id' => 7],  // 3S
            ['area_id' => 6, 'categoria_id' => 8],  // 4S
            ['area_id' => 6, 'categoria_id' => 9],  // 5S
            ['area_id' => 6, 'categoria_id' => 10], // 6S

            // ROBOTICA (Builders P, S, Lego P, S)
            ['area_id' => 7, 'categoria_id' => 23], // Builders P
            ['area_id' => 7, 'categoria_id' => 24], // Builders S
            ['area_id' => 7, 'categoria_id' => 25], // Lego P
            ['area_id' => 7, 'categoria_id' => 26], // Lego S
        ];

        DB::table('area_categoria')->insert($data);
    }
}