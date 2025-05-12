<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Si existe 'senior', elimínala en un bloque separado
        if (Schema::hasColumn('ordenes_pagos', 'senior')) {
            Schema::table('ordenes_pagos', function (Blueprint $table) {
                $table->dropColumn('senior');
            });
        }

        // Agrega las nuevas columnas solo si no existen
        if (!Schema::hasColumn('ordenes_pagos', 'n_orden')) {
            Schema::table('ordenes_pagos', function (Blueprint $table) {
                $table->string('n_orden', 6)
                      ->unique()
                      ->after('id');
            });
        }

        if (!Schema::hasColumn('ordenes_pagos', 'nombre_responsable')) {
            Schema::table('ordenes_pagos', function (Blueprint $table) {
                $table->string('nombre_responsable')
                      ->nullable()
                      ->after('lista_id');
            });
        }
    }

    public function down(): void
    {
        // Elimina las columnas solo si existen
        if (Schema::hasColumn('ordenes_pagos', 'n_orden')) {
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
                $table->string('senior')
                      ->nullable()
                      ->after('estado');
            });
        }
    }
};