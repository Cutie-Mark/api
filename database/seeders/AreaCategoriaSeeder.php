<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreaCategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['area_id' => 3, 'categoria_id' => 1],
            ['area_id' => 3, 'categoria_id' => 2],
            ['area_id' => 3, 'categoria_id' => 3],
            ['area_id' => 3, 'categoria_id' => 4],
            ['area_id' => 3, 'categoria_id' => 5],
            ['area_id' => 3, 'categoria_id' => 6],
            ['area_id' => 3, 'categoria_id' => 7],
            ['area_id' => 3, 'categoria_id' => 8],
            ['area_id' => 3, 'categoria_id' => 9],
            ['area_id' => 3, 'categoria_id' => 10],
            ['area_id' => 4, 'categoria_id' => 6],
            ['area_id' => 4, 'categoria_id' => 7],
            ['area_id' => 4, 'categoria_id' => 8],
            ['area_id' => 4, 'categoria_id' => 9],
            ['area_id' => 4, 'categoria_id' => 10],
            ['area_id' => 5, 'categoria_id' => 8],
            ['area_id' => 5, 'categoria_id' => 9],
            ['area_id' => 5, 'categoria_id' => 10],
            ['area_id' => 6, 'categoria_id' => 11],
            ['area_id' => 6, 'categoria_id' => 12],
            ['area_id' => 6, 'categoria_id' => 13],
            ['area_id' => 6, 'categoria_id' => 14],
            ['area_id' => 6, 'categoria_id' => 15],
            ['area_id' => 6, 'categoria_id' => 16],
            ['area_id' => 7, 'categoria_id' => 17],
            ['area_id' => 7, 'categoria_id' => 18],
            ['area_id' => 7, 'categoria_id' => 19],
            ['area_id' => 7, 'categoria_id' => 20],
            ['area_id' => 7, 'categoria_id' => 21],
            ['area_id' => 7, 'categoria_id' => 22],
            ['area_id' => 8, 'categoria_id' => 6],
            ['area_id' => 8, 'categoria_id' => 7],
            ['area_id' => 8, 'categoria_id' => 8],
            ['area_id' => 8, 'categoria_id' => 9],
            ['area_id' => 8, 'categoria_id' => 10],
            ['area_id' => 9, 'categoria_id' => 23],
            ['area_id' => 9, 'categoria_id' => 24],
            ['area_id' => 9, 'categoria_id' => 25],
            ['area_id' => 9, 'categoria_id' => 26],
        ];
        

        DB::table('area_categoria')->insert($data);
    }
}
