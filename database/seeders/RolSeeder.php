<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;
use App\Models\Servicio;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $rol = Rol::firstOrCreate(['nombre' => 'administrador']);

        $todosLosServicios = Servicio::all();
        $rol->servicios()->sync($todosLosServicios->pluck('id'));
    }
}
