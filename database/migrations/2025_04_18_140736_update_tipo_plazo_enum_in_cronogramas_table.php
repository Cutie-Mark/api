<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cambiar el tipo de la columna para permitir más control
        DB::statement("ALTER TABLE cronogramas ALTER COLUMN tipo_plazo TYPE VARCHAR(25)");

        // Eliminar la constraint anterior si existiera (por seguridad)
        DB::statement("ALTER TABLE cronogramas DROP CONSTRAINT IF EXISTS cronogramas_tipo_plazo_check");

        // Agregar nueva constraint con los nuevos valores válidos
        DB::statement("ALTER TABLE cronogramas ADD CONSTRAINT cronogramas_tipo_plazo_check 
            CHECK (tipo_plazo IN ('Preparacion', 'Lanzamiento', 'Inscripcion', 'Pre Clasificacion', 'Final', 'Premiacion'))");
    }

    public function down(): void
    {
        // Revertir a los valores anteriores con tildes
        DB::statement("ALTER TABLE cronogramas DROP CONSTRAINT IF EXISTS cronogramas_tipo_plazo_check");
        DB::statement("ALTER TABLE cronogramas ADD CONSTRAINT cronogramas_tipo_plazo_check 
            CHECK (tipo_plazo IN ('Preparación', 'Lanzamiento', 'Inscripción', 'Pre calificación', 'Competición Final', 'Premiación'))");
    }
};
