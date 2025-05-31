<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contactos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulante_id')->constrained('postulantes')->onDelete('cascade');
            $table->string('telefono', 15)->nullable();
            $table->enum('tipo_contacto_telefono', [
                'padre/madre', 
                'profesor', 
                'estudiante', 
                'responsable'
            ])->nullable();
            $table->string('email', 55)->nullable();
            $table->enum('tipo_contacto_email', [
                'padre/madre', 
                'profesor', 
                'estudiante', 
                'responsable'
            ])->nullable();
            $table->timestamps();
            
            // Índices para mejorar rendimiento
            $table->index('postulante_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contactos');
    }
};