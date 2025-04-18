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
        Schema::table('olimpiadas', function (Blueprint $table) {
            $table->decimal('precio_inscripcion', 8, 2)->default(16)->after('gestion'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('olimpiadas', function (Blueprint $table) {
            $table->dropColumn('precio_inscripcion');
        });
    }
};
