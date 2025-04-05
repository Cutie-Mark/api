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
            // Validación de datos
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:40|unique:areas,nombre',
            ], [
                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.unique' => 'El nombre del área ya existe, ingrese otro por favor.',
            ]);
    
            // Crear el área
            $area = Area::create($validatedData);
            return response()->json($area, 201);
        
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
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
