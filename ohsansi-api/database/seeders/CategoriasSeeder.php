<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;

class CategoriasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            ['nombre' => '3P', 'minimo_grado' => 3, 'maximo_grado' => 3],
            ['nombre' => '4P', 'minimo_grado' => 4, 'maximo_grado' => 4],
            ['nombre' => '5P', 'minimo_grado' => 5, 'maximo_grado' => 5],
            ['nombre' => '6P', 'minimo_grado' => 6, 'maximo_grado' => 6],
            ['nombre' => '1S', 'minimo_grado' => 7, 'maximo_grado' => 7],
            ['nombre' => '2S', 'minimo_grado' => 8, 'maximo_grado' => 8],
            ['nombre' => '3S', 'minimo_grado' => 9, 'maximo_grado' => 9],
            ['nombre' => '4S', 'minimo_grado' => 10, 'maximo_grado' => 10],
            ['nombre' => '5S', 'minimo_grado' => 11, 'maximo_grado' => 11],
            ['nombre' => '6S', 'minimo_grado' => 12, 'maximo_grado' => 12],
            ['nombre' => 'Guacamayo', 'minimo_grado' => 5, 'maximo_grado' => 6],
            ['nombre' => 'Guanaco', 'minimo_grado' => 7, 'maximo_grado' => 9],
            ['nombre' => 'Londra', 'minimo_grado' => 7, 'maximo_grado' => 9],
            ['nombre' => 'Jucumari', 'minimo_grado' => 10, 'maximo_grado' => 12],
            ['nombre' => 'Bufeo', 'minimo_grado' => 7, 'maximo_grado' => 9],
            ['nombre' => 'Puma', 'minimo_grado' => 10, 'maximo_grado' => 12],
            ['nombre' => 'Primer Nivel', 'minimo_grado' => 7, 'maximo_grado' => 7],
            ['nombre' => 'Segundo Nivel', 'minimo_grado' => 8, 'maximo_grado' => 8],
            ['nombre' => 'Tercer Nivel', 'minimo_grado' => 9, 'maximo_grado' => 9],
            ['nombre' => 'Cuarto Nivel', 'minimo_grado' => 10, 'maximo_grado' => 10],
            ['nombre' => 'Quinto Nivel', 'minimo_grado' => 11, 'maximo_grado' => 11],
            ['nombre' => 'Sexto Nivel', 'minimo_grado' => 12, 'maximo_grado' => 12],
            ['nombre' => 'Builders P', 'minimo_grado' => 5, 'maximo_grado' => 6],
            ['nombre' => 'Builders S', 'minimo_grado' => 7, 'maximo_grado' => 12],
            ['nombre' => 'Lego P', 'minimo_grado' => 5, 'maximo_grado' => 6],
            ['nombre' => 'Lego S', 'minimo_grado' => 7, 'maximo_grado' => 12],
        ];

        foreach ($categorias as $categoria) {
            Categoria::create($categoria);
        }
    }
}
