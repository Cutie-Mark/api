<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ordenes_pagos', function (Blueprint $table) {
            $table->dropColumn('recibo_caja');
        });
    }

    public function down()
    {
        Schema::table('ordenes_pagos', function (Blueprint $table) {
            $table->integer('recibo_caja')->nullable()->after('n_orden');
        });
    }
};
