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
        Schema::create('ordenes_pagos', function (Blueprint $table) {
            $table->id();
            $table->timestamp('fecha_emision')->useCurrent();
            $table->double('monto', 8, 2); // o usar decimal si necesitás más precisión
            $table->string('codigo_lista', 15);
            $table->enum('estado', ['pendiente', 'pagado']);
            $table->unsignedTinyInteger('cantidad_inscripciones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_pagos');
    }
};
