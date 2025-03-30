<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Exception;

class AreaController extends Controller
{
    // Obtener todas las áreas
    public function index()
    {
        return response()->json(Area::all());
    }
    
    // Guardar un área
    
    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|unique:areas,nombre',
            ]);

            $area = Area::create(['nombre' => $request->nombre]);

            return response()->json(['message' => 'Área creada con éxito', 'area' => $area], 201);
        } catch (ValidationException $e) {
            Log::error('Error de validación', ['error' => $e->errors()]);
            return response()->json(['error' => 'Área duplicada o datos inválidos', 'detalles' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Error inesperado', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    

    // 4. Eliminar un área por ID
    public function destroy($id)
    {
        try {
            $area = Area::findOrFail($id);
            $area->delete();
            return response()->json(['message' => 'Área eliminada']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Área no encontrada'], 404);
        }
    }
}
