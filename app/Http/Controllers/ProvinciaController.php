<?php

namespace App\Http\Controllers;

use App\Models\Provincia;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProvinciaController extends Controller
{ 
    /**
     * Listar todas las provincias (con departamento)
     */
    public function index()
    {
        $provincias = Provincia::select('id', 'nombre', 'departamento_id')->get();
        return response()->json($provincias);
    }

    /**
     * Obtener provincia por ID (con departamento)
     */
    public function show($id)
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


    /**
     * Valida nombre único en el mismo departamento (case-insensitive)
     */
    private function checkProvinciaUnica(
        string $nombre, 
        int $departamentoId, 
        ?int $ignoreId = null
    ): void {
        $query = Provincia::whereRaw('LOWER(nombre) = ?', [strtolower($nombre)])
            ->where('departamento_id', $departamentoId);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            $departamento = Departamento::find($departamentoId)->nombre;
            throw new HttpResponseException(response()->json([
                'error' => "El nombre '$nombre' ya existe en el departamento: $departamento"
            ], 409));
        }
    }
}