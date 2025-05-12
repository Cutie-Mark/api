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
        // Agrega 'unidad' solo si no existe
        if (!Schema::hasColumn('ordenes_pagos', 'unidad')) {
            Schema::table('ordenes_pagos', function (Blueprint $table) {
                $table->string('unidad', 50)
                      ->after('nitci')
                      ->default('Inscripción');
            });
        }

        // Agrega 'concepto' solo si no existe
        if (!Schema::hasColumn('ordenes_pagos', 'concepto')) {
            Schema::table('ordenes_pagos', function (Blueprint $table) {
                $table->string('concepto', 255)
                      ->after('unidad');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina 'concepto' solo si existe
        if (Schema::hasColumn('ordenes_pagos', 'concepto')) {
            Schema::table('ordenes_pagos', function (Blueprint $table) {
                $table->dropColumn('concepto');
            });
        }

        // Elimina 'unidad' solo si existe
        if (Schema::hasColumn('ordenes_pagos', 'unidad')) {
            Schema::table('ordenes_pagos', function (Blueprint $table) {
                $table->dropColumn('unidad');
            });
        }
    }
};