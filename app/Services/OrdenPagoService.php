<?php

namespace App\Services;

use App\Models\Lista;
use App\Models\OrdenPago;
use App\Models\Inscripcion;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OrdenPagoService
{
    public function procesarPago(array $data)
    {
        return DB::transaction(function () use ($data) {
            $lista = Lista::where('codigo_lista', $data['codigo_lista'])->firstOrFail();
            $orden = OrdenPago::where('n_orden', $data['n_orden_pago'])
                ->where('lista_id', $lista->id)
                ->firstOrFail();

            $olimpiada = $lista->olimpiada;
            $fechaPago = Carbon::parse($data['fecha']);

            // Validar que la fecha de pago esté dentro del rango de la olimpiada
            if ($fechaPago->lt(Carbon::parse($olimpiada->fecha_inicio)) ||
                $fechaPago->gt(Carbon::parse($olimpiada->fecha_fin))) {
                throw new \Exception("La fecha de pago debe estar entre {$olimpiada->fecha_inicio} y {$olimpiada->fecha_fin}.");
            }

            // Actualizar orden de pago
            $orden->estado = 'pagado';
            $orden->fecha_pago = $fechaPago;
            $orden->save();

            // Actualizar estado de la lista
            $lista->estado = 'Inscripcion Completa';
            $lista->save();

            // Actualizar estado de todas las inscripciones
            Inscripcion::where('lista_id', $lista->id)
                ->update(['estado' => 'Inscripcion Completa']);

            return $orden;
        });
    }
}
