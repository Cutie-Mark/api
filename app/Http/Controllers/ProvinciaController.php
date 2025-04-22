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
     * Crear provincia
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:40',
            'departamento_id' => 'required|exists:departamentos,id'
        ]);

        // Formatear nombre y validar unicidad
        $nombreFormateado = ucwords(strtolower(trim($request->nombre)));
        $this->checkProvinciaUnica($nombreFormateado, $request->departamento_id);

        $provincia = Provincia::create([
            'nombre' => $nombreFormateado,
            'departamento_id' => $request->departamento_id
        ]);

        return response()->json([
            'mensaje' => 'Provincia creada exitosamente',
            'data' => $provincia->load('departamento')
        ], 201);
    }


    /**
     * Listar todas las provincias (con departamento)
     */
    public function index()
    {
        $provincias = Provincia::select('id', 'nombre', 'departamento_id')->get();
        return response()->json([
            //'count' => $provincias->count(),
            'data' => $provincias
        ]);
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
     * Actualizar provincia
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:40',
            'departamento_id' => 'required|exists:departamentos,id'
        ]);

        try {
            $provincia = Provincia::findOrFail($id);
            $nombreFormateado = ucwords(strtolower(trim($request->nombre)));

            $this->checkProvinciaUnica(
                nombre: $nombreFormateado,
                departamentoId: $request->departamento_id,
                ignoreId: $provincia->id
            );

            $provincia->update([
                'nombre' => $nombreFormateado,
                'departamento_id' => $request->departamento_id
            ]);

            return response()->json([
                'mensaje' => 'Provincia actualizada',
                'data' => $provincia->load('departamento')
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