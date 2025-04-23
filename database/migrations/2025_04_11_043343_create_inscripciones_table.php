<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->timestamp('fecha_inscripcion')->useCurrent();

            // Claves Foráneas
            $table->foreignId('postulante_id')->constrained('postulantes')->onDelete('cascade');
            $table->foreignId('responsable_id')->constrained('responsables')->onDelete('cascade');
            $table->foreignId('nivel_competencia_id')->constrained('niveles_competencia')->onDelete('cascade');

            $table->foreignId('colegio_id')->constrained('colegios')->onDelete('cascade');
            $table->foreignId('orden_pago_id')->nullable()->constrained('ordenes_pagos')->onDelete('set null');
            $table->foreignId('lista_id')->nullable()->constrained('listas')->onDelete('cascade');

            // Campos de Contacto
            $table->string('email', 55);
            $table->enum('tipo_contacto_email', ['padre/madre', 'profesor', 'estudiante']);
            $table->string('telefono', 15);
            $table->enum('tipo_contacto_telefono', ['padre/madre', 'profesor', 'estudiante']);

            // Estado
            $table->enum('estado', ['Preinscrito', 'Pago Pendiente', 'Inscripcion Completa'])->default('Preinscrito');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};
