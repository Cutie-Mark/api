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
            ServicioSeeder::class,
            //OlimpiadaSeeder::class,
            //PostulanteSeeder::class,
            //ResponsableSeeder::class,
            //InscripcionSeeder::class,
            //ListaSeeder::class,
        ]);
        
        // User::factory(10)->create();

        /*User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);*/
    }
}
