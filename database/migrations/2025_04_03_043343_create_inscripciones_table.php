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
            $table->foreignUuid('lista_id')->constrained('listas', 'codigo_lista')->onDelete('cascade');
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade');
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('cascade');
            $table->foreignId('colegio_id')->constrained('colegios')->onDelete('cascade');
            $table->foreignId('olimpiada_id')->constrained('olimpiadas')->onDelete('cascade');
            $table->foreignId('orden_pago_id')->nullable()->constrained('ordenes_pagos')->onDelete('set null');

            // Campos de Contacto (alineados con el JSON)
            $table->string('email', 55);
            $table->enum('tipo_contacto_email', ['padre/madre', 'profesor', 'estudiante']);
            $table->string('telefono', 15);
            $table->enum('tipo_contacto_telefono', ['padre/madre', 'profesor', 'estudiante']);

            // Estado (según requerimientos)
            $table->enum('estado', ['pendiente', 'pagado'])->default('pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};