<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Rol;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear o buscar al usuario admin
        $usuario = Usuario::firstOrCreate([
            'nombre_usuario' => 'admin',
        ], [
            'password' => 'admin',
        ]);

        // Obtener o crear el rol administrador
        $rolAdmin = Rol::firstOrCreate(['nombre' => 'administrador']);

        // Asociar el rol al usuario
        $usuario->roles()->syncWithoutDetaching([$rolAdmin->id]);
    }
}
