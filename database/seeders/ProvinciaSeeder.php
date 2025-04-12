<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinciaSeeder extends Seeder
{
    public function run()
    {
        $provincias = [
            // Beni
            ['departamento_id' => 7, 'nombre' => 'Trinidad'],
            ['departamento_id' => 7, 'nombre' => 'Cercado (Beni)'],
            ['departamento_id' => 7, 'nombre' => 'Itenez'],
            ['departamento_id' => 7, 'nombre' => 'General José Ballivián'],
            ['departamento_id' => 7, 'nombre' => 'Segurola'],
            ['departamento_id' => 7, 'nombre' => 'Mamoré'],
            ['departamento_id' => 7, 'nombre' => 'Marbán'],
            ['departamento_id' => 7, 'nombre' => 'Moxos'],
            ['departamento_id' => 7, 'nombre' => 'Vaca Díez'],
            ['departamento_id' => 7, 'nombre' => 'Yacuma'],

            // Chuquisaca
            ['departamento_id' => 4, 'nombre' => 'Sucre'],
            ['departamento_id' => 4, 'nombre' => 'Belisario Boeto'],
            ['departamento_id' => 4, 'nombre' => 'Hernando Siles'],
            ['departamento_id' => 4, 'nombre' => 'Jaime Zudáñez'],
            ['departamento_id' => 4, 'nombre' => 'Juana Azurduy de Padilla'],
            ['departamento_id' => 4, 'nombre' => 'Luis Calvo'],
            ['departamento_id' => 4, 'nombre' => 'Nor Cinti'],
            ['departamento_id' => 4, 'nombre' => 'Oropeza'],
            ['departamento_id' => 4, 'nombre' => 'Sud Cinti'],
            ['departamento_id' => 4, 'nombre' => 'Tomina'],
            ['departamento_id' => 4, 'nombre' => 'Yamparáez'],

            // Cochabamba
            ['departamento_id' => 2, 'nombre' => 'Arani'],
            ['departamento_id' => 2, 'nombre' => 'Arque'],
            ['departamento_id' => 2, 'nombre' => 'Ayopaya'],
            ['departamento_id' => 2, 'nombre' => 'Bolívar'],
            ['departamento_id' => 2, 'nombre' => 'Capinota'],
            ['departamento_id' => 2, 'nombre' => 'José Carrasco'],
            ['departamento_id' => 2, 'nombre' => 'Cercado (Cochabamba)'],
            ['departamento_id' => 2, 'nombre' => 'Chapare'],
            ['departamento_id' => 2, 'nombre' => 'Esteban Arze'],
            ['departamento_id' => 2, 'nombre' => 'Germán Jordán'],
            ['departamento_id' => 2, 'nombre' => 'Mizque'],
            ['departamento_id' => 2, 'nombre' => 'Campero'],
            ['departamento_id' => 2, 'nombre' => 'Punata'],
            ['departamento_id' => 2, 'nombre' => 'Quillacollo'],
            ['departamento_id' => 2, 'nombre' => 'Tapacarí'],
            ['departamento_id' => 2, 'nombre' => 'Tiraque'],

            // La Paz
            ['departamento_id' => 1, 'nombre' => 'Nuestra Señora de La Paz'],
            ['departamento_id' => 1, 'nombre' => 'Abel Iturralde'],
            ['departamento_id' => 1, 'nombre' => 'Aroma'],
            ['departamento_id' => 1, 'nombre' => 'Bautista Saavedra'],
            ['departamento_id' => 1, 'nombre' => 'Caranavi'],
            ['departamento_id' => 1, 'nombre' => 'Eliodoro Camacho'],
            ['departamento_id' => 1, 'nombre' => 'Franz Tamayo'],
            ['departamento_id' => 1, 'nombre' => 'Gualberto Villarroel'],
            ['departamento_id' => 1, 'nombre' => 'Ingaví'],
            ['departamento_id' => 1, 'nombre' => 'Inquisivi'],
            ['departamento_id' => 1, 'nombre' => 'General José Manuel Pando'],
            ['departamento_id' => 1, 'nombre' => 'José Ramón Loayza'],
            ['departamento_id' => 1, 'nombre' => 'Larecaja'],
            ['departamento_id' => 1, 'nombre' => 'Los Andes'],
            ['departamento_id' => 1, 'nombre' => 'Manco Kapac'],
            ['departamento_id' => 1, 'nombre' => 'Muñecas'],
            ['departamento_id' => 1, 'nombre' => 'Nor Yungas'],
            ['departamento_id' => 1, 'nombre' => 'Omasuyos'],
            ['departamento_id' => 1, 'nombre' => 'Pacajes'],
            ['departamento_id' => 1, 'nombre' => 'Pedro Domingo Murillo'],
            ['departamento_id' => 1, 'nombre' => 'Sud Yungas'],

            // Oruro
            ['departamento_id' => 8, 'nombre' => 'Sabaya'],
            ['departamento_id' => 8, 'nombre' => 'Carangas'],
            ['departamento_id' => 8, 'nombre' => 'Cercado (Oruro)'],
            ['departamento_id' => 8, 'nombre' => 'Eduardo Avaroa'],
            ['departamento_id' => 8, 'nombre' => 'Ladislao Cabrera'],
            ['departamento_id' => 8, 'nombre' => 'Litoral de Atacama'],
            ['departamento_id' => 8, 'nombre' => 'Nor Carangas'],
            ['departamento_id' => 8, 'nombre' => 'Pantaleón Dalence'],
            ['departamento_id' => 8, 'nombre' => 'Poopó'],
            ['departamento_id' => 8, 'nombre' => 'Mejillones'],
            ['departamento_id' => 8, 'nombre' => 'Sajama'],
            ['departamento_id' => 8, 'nombre' => 'San Pedro de Totora'],
            ['departamento_id' => 8, 'nombre' => 'Saucarí'],
            ['departamento_id' => 8, 'nombre' => 'Sebastián Pagador'],
            ['departamento_id' => 8, 'nombre' => 'Sud Carangas'],
            ['departamento_id' => 8, 'nombre' => 'Tomás Barrón'],

            // Pando
            ['departamento_id' => 6, 'nombre' => 'Cobija'],
            ['departamento_id' => 6, 'nombre' => 'Abuná'],
            ['departamento_id' => 6, 'nombre' => 'General Federico Román'],
            ['departamento_id' => 6, 'nombre' => 'Madre de Dios'],
            ['departamento_id' => 6, 'nombre' => 'Manuripi'],
            ['departamento_id' => 6, 'nombre' => 'Nicolás Suárez'],

            // Potosí
            ['departamento_id' => 9, 'nombre' => 'Potosí'],
            ['departamento_id' => 9, 'nombre' => 'Alonso de Ibáñez'],
            ['departamento_id' => 9, 'nombre' => 'Antonio Quijarro'],
            ['departamento_id' => 9, 'nombre' => 'Bernardino Bilbao'],
            ['departamento_id' => 9, 'nombre' => 'Charcas'],
            ['departamento_id' => 9, 'nombre' => 'Chayanta'],
            ['departamento_id' => 9, 'nombre' => 'Cornelio Saavedra'],
            ['departamento_id' => 9, 'nombre' => 'Daniel Campos'],
            ['departamento_id' => 9, 'nombre' => 'Enrique Baldivieso'],
            ['departamento_id' => 9, 'nombre' => 'José María Linares'],
            ['departamento_id' => 9, 'nombre' => 'Modesto Omiste'],
            ['departamento_id' => 9, 'nombre' => 'Nor Chichas'],
            ['departamento_id' => 9, 'nombre' => 'Nor Lípez'],
            ['departamento_id' => 9, 'nombre' => 'Rafael Bustillo'],
            ['departamento_id' => 9, 'nombre' => 'Sud Chichas'],
            ['departamento_id' => 9, 'nombre' => 'Sud Lípez'],
            ['departamento_id' => 9, 'nombre' => 'Tomás Frías'],

            // Santa Cruz
            ['departamento_id' => 3, 'nombre' => 'Santa Cruz de la Sierra'],
            ['departamento_id' => 3, 'nombre' => 'Andrés Ibáñez'],
            ['departamento_id' => 3, 'nombre' => 'Ángel Sandóval'],
            ['departamento_id' => 3, 'nombre' => 'Chiquitos'],
            ['departamento_id' => 3, 'nombre' => 'Cordillera'],
            ['departamento_id' => 3, 'nombre' => 'Florida'],
            ['departamento_id' => 3, 'nombre' => 'Germán Busch'],
            ['departamento_id' => 3, 'nombre' => 'Guarayos'],
            ['departamento_id' => 3, 'nombre' => 'Ichilo'],
            ['departamento_id' => 3, 'nombre' => 'Warnes'],
            ['departamento_id' => 3, 'nombre' => 'Velasco'],
            ['departamento_id' => 3, 'nombre' => 'Caballero'],
            ['departamento_id' => 3, 'nombre' => 'Ñuflo de Chaves'],
            ['departamento_id' => 3, 'nombre' => 'Obispo Santistevan'],
            ['departamento_id' => 3, 'nombre' => 'Sara'],
            ['departamento_id' => 3, 'nombre' => 'Vallegrande'],

            // Tarija
            ['departamento_id' => 5, 'nombre' => 'Aniceto Arce'],
            ['departamento_id' => 5, 'nombre' => 'Burdet O\'Connor'],
            ['departamento_id' => 5, 'nombre' => 'Cercado (Tarija)'],
            ['departamento_id' => 5, 'nombre' => 'Eustaquio Méndez'],
            ['departamento_id' => 5, 'nombre' => 'Gran Chaco'],
            ['departamento_id' => 5, 'nombre' => 'José María Avilés'],
            ['departamento_id' => 5, 'nombre' => 'Lago Titicaca'],
        ];

        DB::table('provincias')->insert($provincias);
    }
}
