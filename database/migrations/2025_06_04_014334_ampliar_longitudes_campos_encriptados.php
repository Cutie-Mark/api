<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AmpliarLongitudesCamposEncriptados extends Migration
{
    public function up()
    {
        // POSTULANTES
        Schema::table('postulantes', function (Blueprint $table) {
            // La columna 'ci' originalmente era varchar(10). La ampliamos a 255.
            $table->string('ci', 255)->change();

            // 'nombres' y 'apellidos' suelen definirse como varchar(255),
            // pero si quedaron más cortos originalmente, conviene asegurarse:
            $table->string('nombres', 255)->change();
            $table->string('apellidos', 255)->change();
        });

        // RESPONSABLES
        Schema::table('responsables', function (Blueprint $table) {
            // La columna 'ci' originalmente era varchar(15). La ampliamos a 255.
            $table->string('ci', 255)->change();

            // 'nombre_completo' normalmente era varchar(255), para guardar el cifrado
            $table->string('nombre_completo', 255)->change();
        });
    }

    public function down()
    {
        // Si necesitas revertir, vuelve a los tamaños originales
        Schema::table('postulantes', function (Blueprint $table) {
            $table->string('ci', 10)->change();
            $table->string('nombres', 255)->change();      // Igual que antes
            $table->string('apellidos', 255)->change();    // Igual que antes
        });

        Schema::table('responsables', function (Blueprint $table) {
            $table->string('ci', 15)->change();
            $table->string('nombre_completo', 255)->change(); // Igual que antes
        });
    }
}
