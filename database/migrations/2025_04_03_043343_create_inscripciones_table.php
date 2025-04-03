<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->timestamp('fecha_inscripcion')->useCurrent();
            $table->foreignId('postulante_id')->constrained()->onDelete('cascade');
            $table->foreignId('categoria_id')->constrained()->onDelete('cascade');
            $table->string('email_contacto', 55);
            $table->enum('tipo_contacto_email', ['profesor', 'papa/mama', 'estudiante']);
            $table->string('telefono_contacto', 13);
            $table->enum('tipo_contacto_telefono', ['profesor', 'papa/mama', 'estudiante']);
            $table->foreignId('responsable_id')->constrained()->onDelete('cascade');
            $table->foreignId('lista_id')->constrained()->onDelete('cascade');
            $table->foreignId('id_orden_pago')->nullable()->constrained('ordenes_pagos')->onDelete('set null');
            $table->foreignId('id_colegio')->constrained('colegios')->onDelete('cascade');
            $table->foreignId('id_olimpiada')->constrained('olimpiadas')->onDelete('cascade');
            $table->foreignId('id_area')->constrained('areas')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};
