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
            ['area_id' => 1, 'categoria_id' => 1, 'olimpiada_id' => 1, 'vigente' => true],  // 3P
            ['area_id' => 1, 'categoria_id' => 2, 'olimpiada_id' => 1, 'vigente' => true],  // 4P
            ['area_id' => 1, 'categoria_id' => 3, 'olimpiada_id' => 1, 'vigente' => true],  // 5P
            ['area_id' => 1, 'categoria_id' => 4, 'olimpiada_id' => 1, 'vigente' => true],  // 6P
            ['area_id' => 1, 'categoria_id' => 6, 'olimpiada_id' => 1, 'vigente' => true],  // 2S (ID 6)
            ['area_id' => 1, 'categoria_id' => 7, 'olimpiada_id' => 1, 'vigente' => true],  // 3S
            ['area_id' => 1, 'categoria_id' => 8, 'olimpiada_id' => 1, 'vigente' => true],  // 4S
            ['area_id' => 1, 'categoria_id' => 9, 'olimpiada_id' => 1, 'vigente' => true],  // 5S
            ['area_id' => 1, 'categoria_id' => 10, 'olimpiada_id' => 1, 'vigente' => true], // 6S

            // BIOLOGIA (2S-6S)
            ['area_id' => 2, 'categoria_id' => 6, 'olimpiada_id' => 1, 'vigente' => true],  // 2S
            ['area_id' => 2, 'categoria_id' => 7, 'olimpiada_id' => 1, 'vigente' => true],  // 3S
            ['area_id' => 2, 'categoria_id' => 8, 'olimpiada_id' => 1, 'vigente' => true],  // 4S
            ['area_id' => 2, 'categoria_id' => 9, 'olimpiada_id' => 1, 'vigente' => true],  // 5S
            ['area_id' => 2, 'categoria_id' => 10, 'olimpiada_id' => 1, 'vigente' => true], // 6S

            // FISICA (4S-6S)
            ['area_id' => 3, 'categoria_id' => 8, 'olimpiada_id' => 1, 'vigente' => true],  // 4S
            ['area_id' => 3, 'categoria_id' => 9, 'olimpiada_id' => 1, 'vigente' => true],  // 5S
            ['area_id' => 3, 'categoria_id' => 10, 'olimpiada_id' => 1, 'vigente' => true], // 6S

            // INFORMATICA (Guacamayo, Guanaco, Londra, Jucumari, Bufeo, Puma)
            ['area_id' => 4, 'categoria_id' => 11, 'olimpiada_id' => 1, 'vigente' => true], // Guacamayo
            ['area_id' => 4, 'categoria_id' => 12, 'olimpiada_id' => 1, 'vigente' => true], // Guanaco
            ['area_id' => 4, 'categoria_id' => 13, 'olimpiada_id' => 1, 'vigente' => true], // Londra
            ['area_id' => 4, 'categoria_id' => 14, 'olimpiada_id' => 1, 'vigente' => true], // Jucumari
            ['area_id' => 4, 'categoria_id' => 15, 'olimpiada_id' => 1, 'vigente' => true], // Bufeo
            ['area_id' => 4, 'categoria_id' => 16, 'olimpiada_id' => 1, 'vigente' => true], // Puma

            // MATEMATICAS (Primer Nivel - Sexto Nivel)
            ['area_id' => 5, 'categoria_id' => 17, 'olimpiada_id' => 1, 'vigente' => true], // Primer Nivel
            ['area_id' => 5, 'categoria_id' => 18, 'olimpiada_id' => 1, 'vigente' => true], // Segundo Nivel
            ['area_id' => 5, 'categoria_id' => 19, 'olimpiada_id' => 1, 'vigente' => true], // Tercer Nivel
            ['area_id' => 5, 'categoria_id' => 20, 'olimpiada_id' => 1, 'vigente' => true], // Cuarto Nivel
            ['area_id' => 5, 'categoria_id' => 21, 'olimpiada_id' => 1, 'vigente' => true], // Quinto Nivel
            ['area_id' => 5, 'categoria_id' => 22, 'olimpiada_id' => 1, 'vigente' => true], // Sexto Nivel

            // QUIMICA (2S-6S)
            ['area_id' => 6, 'categoria_id' => 6, 'olimpiada_id' => 1, 'vigente' => true],  // 2S
            ['area_id' => 6, 'categoria_id' => 7, 'olimpiada_id' => 1, 'vigente' => true],  // 3S
            ['area_id' => 6, 'categoria_id' => 8, 'olimpiada_id' => 1, 'vigente' => true],  // 4S
            ['area_id' => 6, 'categoria_id' => 9, 'olimpiada_id' => 1, 'vigente' => true],  // 5S
            ['area_id' => 6, 'categoria_id' => 10, 'olimpiada_id' => 1, 'vigente' => true], // 6S

            // ROBOTICA (Builders P, S, Lego P, S)
            ['area_id' => 7, 'categoria_id' => 23, 'olimpiada_id' => 1, 'vigente' => true], // Builders P
            ['area_id' => 7, 'categoria_id' => 24, 'olimpiada_id' => 1, 'vigente' => true], // Builders S
            ['area_id' => 7, 'categoria_id' => 25, 'olimpiada_id' => 1, 'vigente' => true], // Lego P
            ['area_id' => 7, 'categoria_id' => 26, 'olimpiada_id' => 1, 'vigente' => true], // Lego S
        ];

        DB::table('niveles_competencia')->insert($data);
    }
}