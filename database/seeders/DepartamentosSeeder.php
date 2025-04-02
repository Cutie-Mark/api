<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Departamento;


class DepartamentosSeeder extends Seeder
{
    /**
     * Ejecuta las migraciones de la base de datos.
     *
     * @return void
     */
    public function run()
    {
        // Datos estáticos de los departamentos
        $departamentos = [
            ['nombre' => 'La Paz', 'abreviatura' => 'LP'],
            ['nombre' => 'Cochabamba', 'abreviatura' => 'CBBA'],
            ['nombre' => 'Santa Cruz', 'abreviatura' => 'SCZ'],
            ['nombre' => 'Chuquisaca', 'abreviatura' => 'CH'],
            ['nombre' => 'Tarija', 'abreviatura' => 'TJA'],
            ['nombre' => 'Pando', 'abreviatura' => 'PD'],
            ['nombre' => 'Beni', 'abreviatura' => 'BE'],
            ['nombre' => 'Oruro', 'abreviatura' => 'OR'],
            ['nombre' => 'Potosi', 'abreviatura' => 'PT'],
        ];

        // Insertar los datos de los departamentos en la base de datos
        foreach ($departamentos as $departamento) {
            Departamento::create($departamento);
        }
    }
}
