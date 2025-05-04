<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Servicio;

class ServicioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entidades = [
            'olimpiada',
            'area',
            'categoria',
            'cronograma',
            'fase',
            'inscripcion',
            'ordenpago',
            'nivelcompetencia',
            'lista',
            'rol',
            'servicios'
        ];

        $acciones = ['crear', 'ver', 'editar', 'eliminar'];

        foreach ($entidades as $entidad) {
            foreach ($acciones as $accion) {
                Servicio::firstOrCreate([
                    'nombre' => "{$accion}-{$entidad}"
                ]);
            }
        }
    }
}
