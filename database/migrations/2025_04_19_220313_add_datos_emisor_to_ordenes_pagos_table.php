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
            $table->string('senior')->nullable()->after('estado');
            $table->string('emitido_por')->default('Decanato')->after('senior');
            $table->string('nitci', 7)->after('emitido_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_pagos', function (Blueprint $table) {
            $table->dropColumn(['senior', 'emitido_por', 'nitci']);
        });
    }
};
