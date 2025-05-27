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
        Schema::table('comprobantes', function (Blueprint $table) {
            // First add new columns
            $table->string('n_orden')->nullable();
            $table->string('codigo_lista')->nullable();
            $table->decimal('precio_unitario', 10, 2)->nullable();
            $table->integer('cantidad_inscripciones')->nullable();
            $table->decimal('monto', 10, 2)->nullable();
            $table->dateTime('fecha_pago')->change();
            $table->string('estado')->nullable();
            $table->string('responsable_pago')->nullable();
            $table->string('nitci')->nullable();
        });

        // Then in a separate operation, drop the old columns
        Schema::table('comprobantes', function (Blueprint $table) {
            if (Schema::hasColumns('comprobantes', [
                'codigo',
                'nombre_pagador',
                'url_comprobante',
                'descripcion',
                'ci_nit'
            ])) {
                $table->dropColumn([
                    'codigo',
                    'nombre_pagador',
                    'url_comprobante',
                    'descripcion',
                    'ci_nit'
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            // Revert new columns
            $table->dropColumn([
                'n_orden',
                'codigo_lista',
                'precio_unitario',
                'cantidad_inscripciones',
                'monto',
                'estado',
                'responsable_pago',
                'nitci'
            ]);

            // Restore old columns
            $table->string('codigo');
            $table->string('nombre_pagador');
            $table->string('url_comprobante')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('ci_nit');
        });
    }
};
