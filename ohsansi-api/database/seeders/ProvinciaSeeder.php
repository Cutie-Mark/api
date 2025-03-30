<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinciaSeeder extends Seeder
{
    public function run()
    {
        $provincias = [
            ['departamento_id' => 1, 'nombre' => 'Murillo'],
            ['departamento_id' => 1, 'nombre' => 'Los Andes'],
            ['departamento_id' => 1, 'nombre' => 'Pacajes'],

            ['departamento_id' => 2, 'nombre' => 'Quillacollo'],
            ['departamento_id' => 2, 'nombre' => 'Chapare'],
            ['departamento_id' => 2, 'nombre' => 'Cochabamba'],

            ['departamento_id' => 3, 'nombre' => 'Ñuflo de Chávez'],
            ['departamento_id' => 3, 'nombre' => 'Ichilo'],
            ['departamento_id' => 3, 'nombre' => 'Sara'],

            ['departamento_id' => 4, 'nombre' => 'Oropeza'],
            ['departamento_id' => 4, 'nombre' => 'Zudáñez'],
            ['departamento_id' => 4, 'nombre' => 'Tomina'],

            ['departamento_id' => 5, 'nombre' => 'Cercado'],
            ['departamento_id' => 5, 'nombre' => 'Gran Chaco'],
            ['departamento_id' => 5, 'nombre' => 'Avilés'],

            ['departamento_id' => 6, 'nombre' => 'Madre de Dios'],
            ['departamento_id' => 6, 'nombre' => 'Manuripi'],
            ['departamento_id' => 6, 'nombre' => 'Abuná'],

            ['departamento_id' => 7, 'nombre' => 'Cercado'],
            ['departamento_id' => 7, 'nombre' => 'Vaca Díez'],
            ['departamento_id' => 7, 'nombre' => 'Moxos'],

            ['departamento_id' => 8, 'nombre' => 'Cercado'],
            ['departamento_id' => 8, 'nombre' => 'Sajama'],
            ['departamento_id' => 8, 'nombre' => 'Sur Carangas'],

            ['departamento_id' => 9, 'nombre' => 'Antonio Quijarro'],
            ['departamento_id' => 9, 'nombre' => 'Nor Chichas'],
            ['departamento_id' => 9, 'nombre' => 'Tomás Frías'],
        ];

        DB::table('provincias')->insert($provincias);
    }
}
