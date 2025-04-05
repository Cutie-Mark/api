<?php
namespace Database\Seeders;

use App\Models\Lista;
use App\Models\Responsable;
use Illuminate\Database\Seeder;

class ListaSeeder extends Seeder {
    public function run() {
        $responsables = Responsable::all();

        $listas = [
            [
                'nombre_lista' => 'Lista Matematicas',
                'responsable_id' => $responsables->first()->id,
            ],
            [
                'nombre_lista' => 'Lista Química',
                'responsable_id' => $responsables->get(1)->id,
            ],
            [
                'nombre_lista' => 'Lista Biología',
                'responsable_id' => $responsables->first()->id,
            ],
        ];

        foreach ($listas as $lista) {
            Lista::firstOrCreate(
                ['nombre_lista' => $lista['nombre_lista']],
                $lista
            );
        }
    }
}