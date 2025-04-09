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
        Schema::create('cronogramas', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_plazo', ['Preparación', 'Lanzamiento', 'Inscripción', 'Precalificación','Competición Final', 'Premiación']);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->foreignId('olimpiada_id')->constrained()->onDelete('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cronogramas');
    }
};
