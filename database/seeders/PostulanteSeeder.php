<?php

namespace Database\Seeders;

use App\Models\Postulante;
use Illuminate\Database\Seeder;

class PostulanteSeeder extends Seeder
{
    public function run()
    {
        $postulantes = [
            [
                'nombre' => 'Juan',
                'apellido' => 'Pérez',
                'fecha_nacimiento' => '2005-03-15',
                'provincia_id' => 1,
                'correo_postulante' => 'juan.perez@example.com',
                'ci' => '1234567A',
                'curso' => 10,
            ],
            [
                'nombre' => 'María',
                'apellido' => 'Gómez',
                'fecha_nacimiento' => '2006-07-22',
                'provincia_id' => 2,
                'correo_postulante' => 'maria.gomez@example.com',
                'ci' => '7654321B',
                'curso' => 9,
            ],
            [
                'nombre' => 'Carlos',
                'apellido' => 'López',
                'fecha_nacimiento' => '2004-11-30',
                'provincia_id' => 3,
                'correo_postulante' => 'carlos.lopez@example.com',
                'ci' => '9876543C',
                'curso' => 11,
            ],
            [
                'nombre' => 'Ana',
                'apellido' => 'Martínez',
                'fecha_nacimiento' => '2007-05-10',
                'provincia_id' => 4,
                'correo_postulante' => 'ana.martinez@example.com',
                'ci' => '2345678D',
                'curso' => 8,
            ],
            [
                'nombre' => 'Luis',
                'apellido' => 'Rodríguez',
                'fecha_nacimiento' => '2003-12-05',
                'provincia_id' => 5,
                'correo_postulante' => 'luis.rodriguez@example.com',
                'ci' => '8765432E',
                'curso' => 12,
            ],
            // Nuevos registros
            [
                'nombre' => 'Sofía',
                'apellido' => 'Fernández',
                'fecha_nacimiento' => '2006-02-18',
                'provincia_id' => 6,
                'correo_postulante' => 'sofia.fernandez@example.com',
                'ci' => '3456789F',
                'curso' => 7,
            ],
            [
                'nombre' => 'Diego',
                'apellido' => 'Gutiérrez',
                'fecha_nacimiento' => '2005-09-25',
                'provincia_id' => 7,
                'correo_postulante' => 'diego.gutierrez@example.com',
                'ci' => '4567890G',
                'curso' => 10,
            ],
            [
                'nombre' => 'Valeria',
                'apellido' => 'Silva',
                'fecha_nacimiento' => '2008-04-12',
                'provincia_id' => 8,
                'correo_postulante' => 'valeria.silva@example.com',
                'ci' => '5678901H',
                'curso' => 6,
            ],
            [
                'nombre' => 'Mateo',
                'apellido' => 'Vargas',
                'fecha_nacimiento' => '2004-08-30',
                'provincia_id' => 9,
                'correo_postulante' => 'mateo.vargas@example.com',
                'ci' => '6789012I',
                'curso' => 11,
            ],
            [
                'nombre' => 'Camila',
                'apellido' => 'Rojas',
                'fecha_nacimiento' => '2007-01-14',
                'provincia_id' => 10,
                'correo_postulante' => 'camila.rojas@example.com',
                'ci' => '7890123J',
                'curso' => 7,
            ]
        ];

        foreach ($postulantes as $postulante) {
            Postulante::create($postulante);
        }
    }
}