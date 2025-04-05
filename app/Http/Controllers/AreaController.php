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
                //'olimpiada_id' => 'required|exists:olimpiadas,id',
            ], [
                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.unique' => 'El nombre del área ya existe, ingrese otro por favor.',
                //'olimpiada_id.required' => 'Debe seleccionar una olimpiada.',
                //'olimpiada_id.exists' => 'La olimpiada seleccionada no existe.',
            ]);
    
            // Crear el área
            $area = Area::create([
                'nombre' => $validatedData['nombre'],
            ]);

            
            // Asociar el área a la olimpiada
            //$area->olimpiadas()->attach($validatedData['olimpiada_id']);

            return response()->json($area, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al guardar el área.'], 500);
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
