<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listas', function (Blueprint $table) {
            $table->id(); 
            $table->string('nombre_lista');
            $table->uuid('codigo_lista')->unique(); 
            $table->uuid('id_responsable'); 
            $table->enum('estado', ['pendiente', 'pagado'])->default('pendiente');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->foreign('id_responsable')->references('uuid')->on('responsables')->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listas');
    }
};