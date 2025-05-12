<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Elimina 'senior' si existe
        if (Schema::hasColumn('ordenes_pagos', 'senior')) {
            Schema::table('ordenes_pagos', function (Blueprint $table) {
                $table->dropColumn('senior');
            });
        }

        // Agrega 'n_orden' si no existe
        if (!Schema::hasColumn('ordenes_pagos', 'n_orden')) {
            Schema::table('ordenes_pagos', function (Blueprint $table) {
                $table->string('n_orden', 6)->nullable();
            });
            // Agrega índice único después
            Schema::table('ordenes_pagos', function (Blueprint $table) {
                $table->unique('n_orden');
            });
        }

        // Agrega 'nombre_responsable' si no existe
        if (!Schema::hasColumn('ordenes_pagos', 'nombre_responsable')) {
            Schema::table('ordenes_pagos', function (Blueprint $table) {
                $table->string('nombre_responsable')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Elimina índice único antes de eliminar la columna
        if (Schema::hasColumn('ordenes_pagos', 'n_orden')) {
            Schema::table('ordenes_pagos', function (Blueprint $table) {
                $table->dropUnique(['n_orden']);
            });
            Schema::table('ordenes_pagos', function (Blueprint $table) {
                $table->dropColumn('n_orden');
            });
        }

        if (Schema::hasColumn('ordenes_pagos', 'nombre_responsable')) {
            Schema::table('ordenes_pagos', function (Blueprint $table) {
                $table->dropColumn('nombre_responsable');
            });
        }

        // Vuelve a agregar 'senior' si no existe
        if (!Schema::hasColumn('ordenes_pagos', 'senior')) {
            Schema::table('ordenes_pagos', function (Blueprint $table) {
                $table->string('senior')->nullable();
            });
        }
    }
};