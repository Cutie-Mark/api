<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropColumn([
                'email', 
                'tipo_contacto_email',
                'telefono',
                'tipo_contacto_telefono'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->string('email', 55)->after('lista_id');
            $table->enum('tipo_contacto_email', [
                'padre/madre', 
                'profesor', 
                'estudiante', 
                'responsable'
            ])->after('email');
            $table->string('telefono', 15)->after('tipo_contacto_email');
            $table->enum('tipo_contacto_telefono', [
                'padre/madre', 
                'profesor', 
                'estudiante', 
                'responsable'
            ])->after('telefono');
        });
    }
};