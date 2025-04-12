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
            ['nombre' => '3P', 'minimo_grado' => 3, 'maximo_grado' => 3, 'vigente' => true],
            ['nombre' => '4P', 'minimo_grado' => 4, 'maximo_grado' => 4, 'vigente' => true],
            ['nombre' => '5P', 'minimo_grado' => 5, 'maximo_grado' => 5, 'vigente' => true],
            ['nombre' => '6P', 'minimo_grado' => 6, 'maximo_grado' => 6, 'vigente' => true],
            ['nombre' => '1S', 'minimo_grado' => 7, 'maximo_grado' => 7, 'vigente' => true],
            ['nombre' => '2S', 'minimo_grado' => 8, 'maximo_grado' => 8, 'vigente' => true],
            ['nombre' => '3S', 'minimo_grado' => 9, 'maximo_grado' => 9, 'vigente' => true],
            ['nombre' => '4S', 'minimo_grado' => 10, 'maximo_grado' => 10, 'vigente' => true],
            ['nombre' => '5S', 'minimo_grado' => 11, 'maximo_grado' => 11, 'vigente' => true],
            ['nombre' => '6S', 'minimo_grado' => 12, 'maximo_grado' => 12, 'vigente' => true],
            ['nombre' => 'Guacamayo', 'minimo_grado' => 5, 'maximo_grado' => 6, 'vigente' => true],
            ['nombre' => 'Guanaco', 'minimo_grado' => 7, 'maximo_grado' => 9, 'vigente' => true],
            ['nombre' => 'Londra', 'minimo_grado' => 7, 'maximo_grado' => 9, 'vigente' => true],
            ['nombre' => 'Jucumari', 'minimo_grado' => 10, 'maximo_grado' => 12, 'vigente' => true],
            ['nombre' => 'Bufeo', 'minimo_grado' => 7, 'maximo_grado' => 9, 'vigente' => true],
            ['nombre' => 'Puma', 'minimo_grado' => 10, 'maximo_grado' => 12, 'vigente' => true],
            ['nombre' => 'Primer Nivel', 'minimo_grado' => 7, 'maximo_grado' => 7, 'vigente' => true],
            ['nombre' => 'Segundo Nivel', 'minimo_grado' => 8, 'maximo_grado' => 8, 'vigente' => true],
            ['nombre' => 'Tercer Nivel', 'minimo_grado' => 9, 'maximo_grado' => 9, 'vigente' => true],
            ['nombre' => 'Cuarto Nivel', 'minimo_grado' => 10, 'maximo_grado' => 10, 'vigente' => true],
            ['nombre' => 'Quinto Nivel', 'minimo_grado' => 11, 'maximo_grado' => 11, 'vigente' => true],
            ['nombre' => 'Sexto Nivel', 'minimo_grado' => 12, 'maximo_grado' => 12, 'vigente' => true],
            ['nombre' => 'Builders P', 'minimo_grado' => 5, 'maximo_grado' => 6, 'vigente' => true],
            ['nombre' => 'Builders S', 'minimo_grado' => 7, 'maximo_grado' => 12, 'vigente' => true],
            ['nombre' => 'Lego P', 'minimo_grado' => 5, 'maximo_grado' => 6, 'vigente' => true],
            ['nombre' => 'Lego S', 'minimo_grado' => 7, 'maximo_grado' => 12, 'vigente' => true],
        ];

        foreach ($categorias as $categoriaData) {
            $categoria = Categoria::create($categoriaData);
        }
    }
}
