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
        Schema::table('ordenes_pagos', function (Blueprint $table) {
            $table->timestamp('fecha_pago')
                  ->nullable()
                  ->after('fecha_emision')
                  ->comment('Fecha en que la orden fue marcada como pagada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_pagos', function (Blueprint $table) {
            $table->dropColumn('fecha_pago');
        });
    }
};
