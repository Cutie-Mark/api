<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Añadir columna recibo_caja de tipo BIGINT
        DB::statement("
            ALTER TABLE ordenes_pagos
            ADD COLUMN recibo_caja BIGINT
        ");

        // 2) Crear índice único (constraint) sobre recibo_caja
        DB::statement("
            ALTER TABLE ordenes_pagos
            ADD CONSTRAINT ordenes_pagos_recibo_caja_unique UNIQUE (recibo_caja)
        ");

        // 3) (Opcional) Añadir comentario para documentar la columna
        DB::statement("
            COMMENT ON COLUMN ordenes_pagos.recibo_caja IS 'Número de recibo de caja incremental'
        ");
    }

    public function down(): void
    {
        // 1) Eliminar constraint único
        DB::statement("
            ALTER TABLE ordenes_pagos
            DROP CONSTRAINT IF EXISTS ordenes_pagos_recibo_caja_unique
        ");

        // 2) Eliminar columna recibo_caja
        DB::statement("
            ALTER TABLE ordenes_pagos
            DROP COLUMN IF EXISTS recibo_caja
        ");
    }
};
