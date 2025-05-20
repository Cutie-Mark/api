<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreaCategoriaSeeder extends Seeder
{
    public function run(): void
    {
        // Definimos los mismos pares área–categoría para las olimpiadas 1, 2 y 3
        $baseData = [
            // ASTRONOMIA - ASTROFISICA (3P-6P, 2S-6S)
            ['area_id' => 1, 'categoria_id' => 1,  'vigente' => true],  // 3P
            ['area_id' => 1, 'categoria_id' => 2,  'vigente' => true],  // 4P
            ['area_id' => 1, 'categoria_id' => 3,  'vigente' => true],  // 5P
            ['area_id' => 1, 'categoria_id' => 4,  'vigente' => true],  // 6P
            ['area_id' => 1, 'categoria_id' => 6,  'vigente' => true],  // 2S (ID 6)
            ['area_id' => 1, 'categoria_id' => 7,  'vigente' => true],  // 3S
            ['area_id' => 1, 'categoria_id' => 8,  'vigente' => true],  // 4S
            ['area_id' => 1, 'categoria_id' => 9,  'vigente' => true],  // 5S
            ['area_id' => 1, 'categoria_id' => 10, 'vigente' => true],  // 6S

            // BIOLOGIA (2S-6S)
            ['area_id' => 2, 'categoria_id' => 6,  'vigente' => true],  // 2S
            ['area_id' => 2, 'categoria_id' => 7,  'vigente' => true],  // 3S
            ['area_id' => 2, 'categoria_id' => 8,  'vigente' => true],  // 4S
            ['area_id' => 2, 'categoria_id' => 9,  'vigente' => true],  // 5S
            ['area_id' => 2, 'categoria_id' => 10, 'vigente' => true],  // 6S

            // FISICA (4S-6S)
            ['area_id' => 3, 'categoria_id' => 8,  'vigente' => true],  // 4S
            ['area_id' => 3, 'categoria_id' => 9,  'vigente' => true],  // 5S
            ['area_id' => 3, 'categoria_id' => 10, 'vigente' => true],  // 6S

            // INFORMATICA (Guacamayo, Guanaco, Londra, Jucumari, Bufeo, Puma)
            ['area_id' => 4, 'categoria_id' => 11, 'vigente' => true], // Guacamayo
            ['area_id' => 4, 'categoria_id' => 12, 'vigente' => true], // Guanaco
            ['area_id' => 4, 'categoria_id' => 13, 'vigente' => true], // Londra
            ['area_id' => 4, 'categoria_id' => 14, 'vigente' => true], // Jucumari
            ['area_id' => 4, 'categoria_id' => 15, 'vigente' => true], // Bufeo
            ['area_id' => 4, 'categoria_id' => 16, 'vigente' => true], // Puma

            // MATEMATICAS (Primer Nivel - Sexto Nivel)
            ['area_id' => 5, 'categoria_id' => 17, 'vigente' => true], // Primer Nivel
            ['area_id' => 5, 'categoria_id' => 18, 'vigente' => true], // Segundo Nivel
            ['area_id' => 5, 'categoria_id' => 19, 'vigente' => true], // Tercer Nivel
            ['area_id' => 5, 'categoria_id' => 20, 'vigente' => true], // Cuarto Nivel
            ['area_id' => 5, 'categoria_id' => 21, 'vigente' => true], // Quinto Nivel
            ['area_id' => 5, 'categoria_id' => 22, 'vigente' => true], // Sexto Nivel

            // QUIMICA (2S-6S)
            ['area_id' => 6, 'categoria_id' => 6,  'vigente' => true],  // 2S
            ['area_id' => 6, 'categoria_id' => 7,  'vigente' => true],  // 3S
            ['area_id' => 6, 'categoria_id' => 8,  'vigente' => true],  // 4S
            ['area_id' => 6, 'categoria_id' => 9,  'vigente' => true],  // 5S
            ['area_id' => 6, 'categoria_id' => 10, 'vigente' => true],  // 6S

            // ROBOTICA (Builders P, S, Lego P, S)
            ['area_id' => 7, 'categoria_id' => 23, 'vigente' => true], // Builders P
            ['area_id' => 7, 'categoria_id' => 24, 'vigente' => true], // Builders S
            ['area_id' => 7, 'categoria_id' => 25, 'vigente' => true], // Lego P
            ['area_id' => 7, 'categoria_id' => 26, 'vigente' => true], // Lego S
        ];

        // Para cada entrada de $baseData, vamos a duplicarla en olimpiada_id = 1, 2 y 3
        $allInsertions = [];
        foreach ([1, 2, 3] as $olimpiadaId) {
            foreach ($baseData as $row) {
                $allInsertions[] = array_merge($row, [
                    'olimpiada_id' => $olimpiadaId,
                ]);
            }
        }

        // Insertamos todos los registros de una sola vez
        DB::table('niveles_competencia')->insert($allInsertions);
    }
}