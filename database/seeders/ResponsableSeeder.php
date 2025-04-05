<?php

namespace Database\Seeders;

use App\Models\Responsable;
use App\Models\Postulante;
use Illuminate\Database\Seeder;

class ResponsableSeeder extends Seeder
{
    public function run()
    {
        $responsables = [
            [
                'nombre' => 'JOFRE',
                'apellido' => 'TICONA PLATA',
                'ci' => '1234567A',
                'telefono' => '77777777',
                'es_profesor' => true,
                'email' => 'jofre@example.com'
            ],
            [
                'nombre' => 'MARIA',
                'apellido' => 'GOMEZ',
                'ci' => '7654321B',
                'telefono' => '88888888',
                'es_profesor' => false,
                'email' => 'maria@example.com'
            ],
            [
                'nombre' => 'CARLOS',
                'apellido' => 'LOPEZ',
                'ci' => '9876543C',
                'telefono' => '99999999',
                'es_profesor' => true,
                'email' => 'carlos@example.com'
            ],
        ];

        foreach ($responsables as $responsable) {
            Responsable::create($responsable);
        }

        // Responsable que también es postulante (usando datos de Luis Rodríguez)
        $postulante = Postulante::where('ci', '8765432E')->first();

        Responsable::create([
            'nombre' => $postulante->nombre,
            'apellido' => $postulante->apellido,
            'ci' => $postulante->ci,
            'telefono' => '60000000', // Teléfono diferente
            'es_profesor' => false,
            'email' => $postulante->correo_postulante
        ]);
    }
}