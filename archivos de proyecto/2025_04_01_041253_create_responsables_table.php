<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responsables', function (Blueprint $table) {
            $table->id(); 
            $table->string('nombre_completo');
            $table->string('ci')->unique();
            $table->string('email')->unique();
            $table->string('telefono', 8);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responsables');
    }
};