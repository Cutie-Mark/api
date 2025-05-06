<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cronogramas', function (Blueprint $table) {
            $table->renameColumn('tipo_plazo', 'tipo_plazo_enum');
        });

        Schema::table('cronogramas', function (Blueprint $table) {
            $table->string('tipo_plazo', 40)->after('id_fase')->nullable();
        });

        DB::table('cronogramas')->update([
            'tipo_plazo' => DB::raw('tipo_plazo_enum')
        ]);

        Schema::table('cronogramas', function (Blueprint $table) {
            $table->dropColumn('tipo_plazo_enum');
        });

        Schema::table('cronogramas', function (Blueprint $table) {
            $table->string('tipo_plazo', 40)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cronogramas', function (Blueprint $table) {
            $table->dropColumn('tipo_plazo');
        });

        Schema::table('cronogramas', function (Blueprint $table) {
            $table->enum('tipo_plazo', ['Preparación', 'Lanzamiento', 'Inscripción', 'Precalificación','Competición Final', 'Premiación']);
        });
    }
};
