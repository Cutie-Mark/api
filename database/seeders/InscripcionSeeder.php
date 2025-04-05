<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\{
    Postulante,
    Responsable,
    Lista,
    Inscripcion
};
use Illuminate\Support\Facades\DB;

class InscripcionSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            // Tipo 1: Inscripción con datos existentes (Postulante ID 1 y Responsable ID 1)
            $this->createInscripcion(
                Postulante::find(1),
                Responsable::find(1),
                categoria_id: 8,   // 4S (Física)
                area_id: 3         // Física
            );

            // Tipo 2: Nuevos datos (Postulante y Responsable nuevos)
            $postulante2 = Postulante::create([
                'nombre' => 'Roberto',
                'apellido' => 'García',
                'fecha_nacimiento' => '2005-03-15',
                'provincia_id' => 1,
                'correo_postulante' => 'roberto@example.com',
                'ci' => '7979779X',
                'curso' => 10
            ]);

            $responsable2 = Responsable::create([
                'nombre' => 'Ana',
                'apellido' => 'Martínez',
                'ci' => '11223344Y',
                'telefono' => '59171357924',
                'es_profesor' => false,
                'email' => 'ana@example.com'
            ]);

            $this->createInscripcion(
                $postulante2,
                $responsable2,
                categoria_id: 11,  // Guacamayo (Informática)
                area_id: 4         // Informática
            );

            // Tipo 3: Postulante es su propio responsable
            $ciCompartido = '8765432E';

            // Crear postulante (si no existe)
            $postulante3 = Postulante::firstOrCreate(
                ['ci' => $ciCompartido],
                [
                    'nombre' => 'Luis',
                    'apellido' => 'Rodríguez',
                    'fecha_nacimiento' => '2003-12-05',
                    'provincia_id' => 5,
                    'correo_postulante' => 'luis@example.com',
                    'curso' => 12
                ]
            );

            // Crear responsable (si no existe)
            $responsable3 = Responsable::firstOrCreate(
                ['ci' => $ciCompartido],
                [
                    'nombre' => 'Luis',
                    'apellido' => 'Rodríguez',
                    'telefono' => '60000000',
                    'es_profesor' => false,
                    'email' => 'luis@example.com'
                ]
            );

            $this->createInscripcion(
                $postulante3,
                $responsable3,
                categoria_id: 23,  // Builders P (Robótica)
                area_id: 7         // Robótica
            );

            // Tipo 4: Inscripción masiva (3 nuevos postulantes y responsables)
            foreach (range(1, 3) as $i) {
                $postulante = Postulante::create([
                    'nombre' => 'Estudiante ' . $i,
                    'apellido' => 'Apellido ' . $i,
                    'fecha_nacimiento' => '200' . (5 + $i) . '-01-01',
                    'provincia_id' => $i,
                    'correo_postulante' => 'estudiante' . $i . '@example.com',
                    'ci' => '111111' . $i,
                    'curso' => 9 + $i
                ]);

                $responsable = Responsable::create([
                    'nombre' => 'Responsable ' . $i,
                    'apellido' => 'Apellido ' . $i,
                    'ci' => '999999' . $i,
                    'telefono' => '7000000' . $i,
                    'es_profesor' => ($i % 2 == 0),
                    'email' => 'responsable' . $i . '@example.com'
                ]);

                $this->createInscripcion(
                    $postulante,
                    $responsable,
                    categoria_id: 17 + $i, // Primer Nivel, Segundo Nivel, etc.
                    area_id: 5             // Matemáticas
                );
            }
        });
    }

    private function createInscripcion($postulante, $responsable, $categoria_id, $area_id)
    {
        // Crear lista automáticamente
        $lista = Lista::firstOrCreate(
            ['responsable_id' => $responsable->id],
            [
                'nombre_lista' => 'LISTA-' . Str::random(4),
                'codigo_lista' => 'COD-' . Str::upper(Str::random(6))
            ]
        );

        // Crear inscripción
        Inscripcion::create([
            'postulante_id' => $postulante->id,
            'categoria_id' => $categoria_id,
            'email_contacto' => $postulante->correo_postulante,
            'tipo_contacto_email' => 'estudiante',
            'telefono_contacto' => $responsable->telefono,
            'tipo_contacto_telefono' => 'papa/mama',
            'responsable_id' => $responsable->id,
            'lista_id' => $lista->id,
            'id_colegio' => 1,       // Colegio existente
            'id_olimpiada' => 1,     // Olimpiada existente
            'id_area' => $area_id,
            'estado' => 'pendiente_pago'
        ]);
    }
}