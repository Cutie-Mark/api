<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ordenes_pagos');

        Schema::create('ordenes_pagos', function (Blueprint $table) {
            $table->id();
            $table->string('n_orden', 6)->unique();
            $table->timestamp('fecha_emision')->useCurrent();
            $table->decimal('monto', 8, 2);
            $table->enum('estado', ['pendiente', 'pagado']);
            $table->unsignedTinyInteger('cantidad_inscripciones');
            $table->foreignId('lista_id')
                  ->constrained('listas')
                  ->onDelete('cascade');
            $table->string('nombre_responsable')->nullable();
            $table->string('emitido_por')->default('Decanato');
            $table->string('nitci', 15);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_pagos');
    }
};
