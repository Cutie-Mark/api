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
        Schema::table('inscripciones', function (Blueprint $table) {
            // Índices para mejorar consultas de búsqueda y filtrado
            $table->index(['postulante_id', 'nivel_competencia_id'], 'idx_postulante_nivel');
            $table->index(['nivel_competencia_id', 'estado'], 'idx_nivel_estado');
            $table->index(['lista_id', 'estado'], 'idx_lista_estado');
            $table->index(['responsable_id', 'estado'], 'idx_responsable_estado');
            $table->index('estado', 'idx_estado');
            $table->index('fecha_inscripcion', 'idx_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropIndex('idx_postulante_nivel');
            $table->dropIndex('idx_nivel_estado');
            $table->dropIndex('idx_lista_estado');
            $table->dropIndex('idx_responsable_estado');
            $table->dropIndex('idx_estado');
            $table->dropIndex('idx_fecha');
        });
    }
};
