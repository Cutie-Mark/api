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
                'nombre_completo' => 'JOFRE TICONA PLATA',
                'ci' => '1234567A',
                'telefono' => '77777777',
                'email' => 'jofre@example.com'
            ],
            [
                'nombre_completo' => 'MARIA GOMEZ',
                'ci' => '7654321B',
                'telefono' => '88888888',
                'email' => 'maria@example.com'
            ],
            [
                'nombre_completo' => 'CARLOS LOPEZ',
                'ci' => '9876543C',
                'telefono' => '99999999',
                'email' => 'carlos@example.com'
            ],
        ];

        foreach ($responsables as $responsable) {
            Responsable::create($responsable);
        }

        // Buscar un postulante existente por CI desencriptado
        $postulantes = Postulante::all();
        $postulante = null;
        foreach ($postulantes as $p) {
            if ($p->ci === '8765432E') {
                $postulante = $p;
                break;
            }
        }

        if ($postulante) {
            Responsable::create([
                'nombre_completo' => $postulante->nombres . ' ' . $postulante->apellidos,
                'ci' => $postulante->ci,
                'telefono' => '60000000', // Teléfono diferente
                'email' => $postulante->email
            ]);
        }
    }
}