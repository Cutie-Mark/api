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
            
            // Claves foráneas ajustadas a convenciones de Laravel
            $table->foreignId('postulante_id')->constrained('postulantes')->onDelete('cascade');
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('cascade');
            $table->foreignId('lista_id')->constrained('listas')->onDelete('cascade');
            $table->foreignId('orden_pago_id')->nullable()->constrained('ordenes_pagos')->onDelete('set null');
            $table->foreignId('colegio_id')->constrained('colegios')->onDelete('cascade');
            $table->foreignId('olimpiada_id')->constrained('olimpiadas')->onDelete('cascade');
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade');
            
            // Campos de contacto
            $table->string('email_contacto', 55);
            $table->enum('tipo_contacto_email', ['profesor', 'papa/mama', 'estudiante']);
            $table->string('telefono_contacto', 13);
            $table->enum('tipo_contacto_telefono', ['profesor', 'papa/mama', 'estudiante']);
            
            // Estado de la inscripción
            $table->enum('estado', ['pendiente', 'pagado', 'rechazado', 'aprobado'])->default('pendiente');
            //$table->timestamps();
        });
    }
        //$table->foreignId('responsable_id')->references('id_responsable')->on('responsable')->onDelete('cascade'); se quito por redundancia

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};
