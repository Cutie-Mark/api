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
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id('id_comprobante');
            $table->unsignedBigInteger('orden_pago_id')->unique(); // Relación 1 a 1
            $table->string('codigo', 45);
            $table->string('nombre_pagador', 100);
            $table->string('url_comprobante', 100);
            $table->date('fecha_pago');
            $table->string('ci_nit', 10);
            $table->text('descripcion')->default('')->nullable(false);
            $table->timestamps();
            
            $table->foreign('orden_pago_id')
                  ->references('id')
                  ->on('orden_pagos')
                  ->onDelete('cascade'); // si se borra la orden, se borra el comprobante
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
