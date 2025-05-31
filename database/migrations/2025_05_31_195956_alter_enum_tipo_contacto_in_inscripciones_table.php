<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Quita la antigua constraint CHECK sobre tipo_contacto_email
        DB::statement("
            ALTER TABLE inscripciones
            DROP CONSTRAINT IF EXISTS inscripciones_tipo_contacto_email_check
        ");

        // 2) Crea la nueva con el valor 'responsable' añadido
        DB::statement("
            ALTER TABLE inscripciones
            ADD CONSTRAINT inscripciones_tipo_contacto_email_check
            CHECK (tipo_contacto_email IN (
                'padre/madre',
                'profesor',
                'estudiante',
                'responsable'
            ))
        ");

        // 3) Haz lo mismo para tipo_contacto_telefono
        DB::statement("
            ALTER TABLE inscripciones
            DROP CONSTRAINT IF EXISTS inscripciones_tipo_contacto_telefono_check
        ");
        DB::statement("
            ALTER TABLE inscripciones
            ADD CONSTRAINT inscripciones_tipo_contacto_telefono_check
            CHECK (tipo_contacto_telefono IN (
                'padre/madre',
                'profesor',
                'estudiante',
                'responsable'
            ))
        ");
    }

    public function down(): void
    {
        // Revertir: volvemos al CHECK sin 'responsable'
        DB::statement("
            ALTER TABLE inscripciones
            DROP CONSTRAINT IF EXISTS inscripciones_tipo_contacto_email_check
        ");
        DB::statement("
            ALTER TABLE inscripciones
            ADD CONSTRAINT inscripciones_tipo_contacto_email_check
            CHECK (tipo_contacto_email IN (
                'padre/madre',
                'profesor',
                'estudiante'
            ))
        ");

        DB::statement("
            ALTER TABLE inscripciones
            DROP CONSTRAINT IF EXISTS inscripciones_tipo_contacto_telefono_check
        ");
        DB::statement("
            ALTER TABLE inscripciones
            ADD CONSTRAINT inscripciones_tipo_contacto_telefono_check
            CHECK (tipo_contacto_telefono IN (
                'padre/madre',
                'profesor',
                'estudiante'
            ))
        ");
    }
};
