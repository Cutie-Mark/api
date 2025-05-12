<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Servicio;

class ServicioSeeder extends Seeder
{
    public function run(): void
    {
        $servicios = [
            'crear olimpiada',
            'agregar un área',
            'dar de baja una área',
            'habilitar un área',
            'agregar una categoría',
            'editar categoría',
            'dar de baja una categoría',
            'generara plantilla de excel',
            'subir excel para olimpiada',
            'definir fases de una olimpiada',
            'asociar áreas a una olimpiada',
            'ver versiones de olimpiada',
            'crear usuarios',
            'generar reporte de inscripción',
            'crear un rol',
            'asignar privilegios a roles',
            'asignar roles a un usuario'
        ];

        foreach ($servicios as $index => $servicio) {
            Servicio::updateOrCreate(
                ['id' => $index + 1],
                ['nombre' => strtolower($servicio)]
            );
        }
    }
}
