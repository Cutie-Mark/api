<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ColegioSeeder extends Seeder
{
    public function run()
    {
        $colegios = [
            ['nombre' => 'Instituto Americano'],
            ['nombre' => 'Colegio San Ignacio'],
            ['nombre' => 'Colegio Irlandes'],
            ['nombre' => 'Colegio San José'],
            ['nombre' => 'Colegio Bolívar'],
            ['nombre' => 'Colegio San Agustin'],
            ['nombre' => 'Colegio La Salle'],
            ['nombre' => 'Colegio Alemán'],
            ['nombre' => 'Colegio San Juan'],
            ['nombre' => 'Colegio Don Bosco'],
            ['nombre' => 'Colegio Franco Boliviano'],
            ['nombre' => 'Unidad Educativa Loyola'],
            ['nombre' => 'Colegio Calvert'],
            ['nombre' => 'Colegio Ave María'],
            ['nombre' => 'Colegio Espíritu Santo'],
            ['nombre' => 'Colegio Santa Ana'],
            ['nombre' => 'Colegio Domingo Savio'],
            ['nombre' => 'Colegio Juan Pablo II'],
            ['nombre' => 'Colegio Cambridge'],
            ['nombre' => 'Colegio Cristiano Semilla de Vida'],
            ['nombre' => 'Colegio Cristiano Betania'],
            ['nombre' => 'Colegio Saint Andrew’s'],
            ['nombre' => 'Colegio Eagles School'],
            ['nombre' => 'Colegio Británico'],
            ['nombre' => 'Colegio Cristiano Maranatha'],
            ['nombre' => 'Colegio del Sol'],
            ['nombre' => 'Colegio Montessori'],
            ['nombre' => 'Colegio Isaac Attie'],
            ['nombre' => 'Colegio Jesús Maestro'],
            ['nombre' => 'Colegio Loretto'],
            ['nombre' => 'Colegio María Auxiliadora'],
            ['nombre' => 'Colegio Cristo Rey'],
            ['nombre' => 'Colegio Aranjuez'],
            ['nombre' => 'Colegio Charles Darwin'],
            ['nombre' => 'Colegio Santa Teresa'],
            ['nombre' => 'Colegio Internacional de Cochabamba'],
            ['nombre' => 'Colegio Evangélico Emanuel'],
            ['nombre' => 'Colegio Pedro Poveda'],
            ['nombre' => 'Colegio San Lorenzo'],
            ['nombre' => 'Colegio San Antonio'],
            ['nombre' => 'Colegio Sagrados Corazones'],
            ['nombre' => 'Colegio Simón Bolívar'],
            ['nombre' => 'Colegio René Moreno'],
            ['nombre' => 'Colegio José Malky'],
            ['nombre' => 'Colegio Amerinst'],
            ['nombre' => 'Colegio Genaro Frontanilla'],
            ['nombre' => 'Colegio Sucre'],
            ['nombre' => 'Colegio Junín'],
            ['nombre' => 'Colegio Nacional Pichincha'],
            ['nombre' => 'Colegio Nacional Aniceto Arce'],
            ['nombre' => 'Colegio Nacional La Paz'],
            ['nombre' => 'Colegio Nacional Oruro'],
            ['nombre' => 'Colegio Nacional Tarija'],
            ['nombre' => 'Colegio Nacional Trinidad'],
            ['nombre' => 'Colegio Nacional Santa Cruz'],
            ['nombre' => 'Colegio Nacional Sucre'],
            ['nombre' => 'Colegio Nacional Ayacucho'],
            ['nombre' => 'Colegio Nacional Mejillones'],
            ['nombre' => 'Colegio Nacional José Ballivián'],
            ['nombre' => 'Colegio Nacional Germán Busch'],
        ];

        DB::table('colegios')->insert($colegios);
    }
}
