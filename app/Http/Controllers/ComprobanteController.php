<?php

namespace App\Http\Controllers;
use App\Models\Comprobante;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ComprobanteController extends Controller
{
    public function getByCodigo($codigo)
    {
        $comprobante = Comprobante::where('codigo', $codigo)
                                  ->first();

        if (!$comprobante) {
            return response()->json(['error' => 'Comprobante no encontrado'], 404);
        }

        return response()->json($comprobante);
    }

    public function getByOrdenId($ordenId)
    {
        $comprobantes = Comprobante::where('orden_pago_id', $ordenId)->get();

        return response()->json($comprobantes);
    }

    public function getByCINIT($nit)
    {
        $comprobantes = Comprobante::where('ci_nit', $nit)->get();

        return response()->json($comprobantes);
    }

}
