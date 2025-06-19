<?php

namespace App\Http\Controllers;

use App\Models\Provincia;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProvinciaController extends Controller
{ 
    /**
     * Listar todas las provincias (con departamento)
     */
    public function listar()
    {
        $provincias = Cache::remember('catalogo_provincias_base', now()->addDays(7), function () {
            return Provincia::select('id', 'nombre', 'departamento_id')->get()->toArray();
        });

        return response()->json($provincias);
    }

    /**
     * Obtener provincia por ID (con departamento)
     */
    public function mostrar($id)
    {
        try {
            $provincia = Provincia::with('departamento')->findOrFail($id);
            return response()->json([
                'mensaje' => 'Provincia encontrada',
                'data' => $provincia
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Provincia no encontrada'], 404);
        }
    }

}