<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        // Aumentar a VARCHAR(7)
        DB::statement('ALTER TABLE ordenes_pagos ALTER COLUMN n_orden TYPE VARCHAR(7);');
    }

    public function down()
    {
        // Volver a VARCHAR(6)
        DB::statement('ALTER TABLE ordenes_pagos ALTER COLUMN n_orden TYPE VARCHAR(6);');
    }
};
