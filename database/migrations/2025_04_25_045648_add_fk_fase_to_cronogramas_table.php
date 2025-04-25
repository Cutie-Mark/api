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
        Schema::table('cronogramas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_fase')->nullable()->after('tipo_plazo');  
            $table->foreign('id_fase')->references('id')->on('fases')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cronogramas', function (Blueprint $table) {
            $table->dropForeign(['id_fase']);
            $table->dropColumn('id_fase');
        });
    }
};
