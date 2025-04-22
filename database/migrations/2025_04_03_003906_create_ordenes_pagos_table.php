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
            $table->decimal('monto', 8, 2);
            $table->enum('estado', ['pendiente', 'pagado']);
            $table->unsignedTinyInteger('cantidad_inscripciones');
            $table->foreignId('lista_id')->constrained('listas')->onDelete('cascade');
            $table->string('senior')->nullable()->after('estado');
            $table->string('emitido_por')->default('Decanato')->after('senior');
            $table->string('nitci', 15)->after('emitido_por');
            $table->timestamps();
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
