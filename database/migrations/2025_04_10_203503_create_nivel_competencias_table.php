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
        Schema::create('niveles_competencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained()->onDelete('cascade');
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade');
            $table->foreignId('olimpiada_id')->constrained('olimpiadas')->onDelete('cascade');
            
            $table->boolean('vigente')->default(true);
            $table->timestamps();

            $table->unique(['categoria_id', 'area_id', 'olimpiada_id'], 'nivel_competencia_unico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('niveles_competencia');
    }
};
