<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ComprobanteController extends Controller
{
    /**
     * Display the specified comprobante.
     */
    public function show($id)
    {
        $comprobante = Comprobante::findOrFail($id);
        
        return response()->json([
            'id' => $comprobante->id_comprobante,
            'orden_pago_id' => $comprobante->orden_pago_id,
            'n_orden' => $comprobante->n_orden,
            'codigo_lista' => $comprobante->codigo_lista,
            'fecha_pago' => $comprobante->fecha_pago->format('Y-m-d H:i:s'),
            'precio_unitario' => number_format($comprobante->precio_unitario, 2),
            'cantidad_inscripciones' => $comprobante->cantidad_inscripciones,
            'monto' => number_format($comprobante->monto, 2),
            'estado' => $comprobante->estado,
            'responsable_pago' => $comprobante->responsable_pago,
            'nitci' => $comprobante->nitci
        ], 200);
    }
}
