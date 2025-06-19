<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;
use App\Models\Servicio;
use Illuminate\Support\Facades\Log;

class ServicioRolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Rol::where('nombre', 'admin')->first();
        $allServices = Servicio::all();

        if ($adminRole) {
            $adminRole->servicios()->sync($allServices->pluck('id'));
            Log::info('ServicioRolSeeder: Synced all services to admin role.');
        } else {
            Log::warning('ServicioRolSeeder: Admin role not found.');
        }

    }
}
