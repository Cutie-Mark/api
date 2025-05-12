<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Actualizar restricciones y datos para 'tipo_contacto_email'
        DB::statement('ALTER TABLE inscripciones DROP CONSTRAINT IF EXISTS inscripciones_tipo_contacto_email_check');
        DB::update("UPDATE inscripciones SET tipo_contacto_email = 'Mamá/Papá' WHERE tipo_contacto_email = 'padre/madre'");
        DB::update("UPDATE inscripciones SET tipo_contacto_email = 'Postulante' WHERE tipo_contacto_email = 'estudiante'");
        DB::statement("ALTER TABLE inscripciones ADD CONSTRAINT inscripciones_tipo_contacto_email_check CHECK (tipo_contacto_email IN ('Mamá/Papá', 'Profesor', 'Postulante'))");

        // Actualizar restricciones y datos para 'tipo_contacto_telefono'
        DB::statement('ALTER TABLE inscripciones DROP CONSTRAINT IF EXISTS inscripciones_tipo_contacto_telefono_check');
        DB::update("UPDATE inscripciones SET tipo_contacto_telefono = 'Mamá/Papá' WHERE tipo_contacto_telefono = 'padre/madre'");
        DB::update("UPDATE inscripciones SET tipo_contacto_telefono = 'Postulante' WHERE tipo_contacto_telefono = 'estudiante'");
        DB::statement("ALTER TABLE inscripciones ADD CONSTRAINT inscripciones_tipo_contacto_telefono_check CHECK (tipo_contacto_telefono IN ('Mamá/Papá', 'Profesor', 'Postulante'))");
    }

    public function down(): void
    {
        // Revertir restricciones y datos para 'tipo_contacto_email'
        DB::statement('ALTER TABLE inscripciones DROP CONSTRAINT IF EXISTS inscripciones_tipo_contacto_email_check');
        DB::update("UPDATE inscripciones SET tipo_contacto_email = 'padre/madre' WHERE tipo_contacto_email = 'Mamá/Papá'");
        DB::update("UPDATE inscripciones SET tipo_contacto_email = 'estudiante' WHERE tipo_contacto_email = 'Postulante'");
        DB::statement("ALTER TABLE inscripciones ADD CONSTRAINT inscripciones_tipo_contacto_email_check CHECK (tipo_contacto_email IN ('padre/madre', 'profesor', 'estudiante'))");

        // Revertir restricciones y datos para 'tipo_contacto_telefono'
        DB::statement('ALTER TABLE inscripciones DROP CONSTRAINT IF EXISTS inscripciones_tipo_contacto_telefono_check');
        DB::update("UPDATE inscripciones SET tipo_contacto_telefono = 'padre/madre' WHERE tipo_contacto_telefono = 'Mamá/Papá'");
        DB::update("UPDATE inscripciones SET tipo_contacto_telefono = 'estudiante' WHERE tipo_contacto_telefono = 'Postulante'");
        DB::statement("ALTER TABLE inscripciones ADD CONSTRAINT inscripciones_tipo_contacto_telefono_check CHECK (tipo_contacto_telefono IN ('padre/madre', 'profesor', 'estudiante'))");
    }
};