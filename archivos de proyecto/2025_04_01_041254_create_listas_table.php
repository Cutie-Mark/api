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
            $table->string('codigo_lista', 6)->unique();  
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->foreignId('responsable_id')->constrained('responsables') ->onDelete('cascade'); 
            
            $table->timestamps();

            $table->enum('estado', ['pendiente', 'pagado'])->default('pendiente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listas');
    }
};