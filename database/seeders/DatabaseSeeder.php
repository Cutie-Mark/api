<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            OlimpiadaSeeder::class,
            DepartamentosSeeder::class,
            ProvinciaSeeder::class,
            AreasSeeder::class,
            CategoriasSeeder::class,
            AreaCategoriaSeeder::class,
            ColegioSeeder::class,
            FaseSeeder::class,
            CronogramaSeeder::class, // Agregamos el seeder de cronograma
            ServicioSeeder::class,
            RolSeeder::class,
            UsuarioSeeder::class,
            ServicioRolSeeder::class
        ]);
    }
}
