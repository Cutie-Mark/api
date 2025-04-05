<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;


class CategoriaController extends Controller
{
    // Obtener todas las categorías
    public function index()
    {
        return response()->json(Categoria::all());
    }

    // Obtener todas las categorías con las áreas relacionadas
    public function indexWithAreas()
    {
        $categorias = Categoria::with('areas:id,nombre')->get();
        
        return response()->json($categorias);
    }

    // Registrar una nueva categoría
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nombre' => 'required|string|unique:categorias,nombre',
                'minimo_grado' => 'required|integer|min:1|max:12',
                'maximo_grado' => 'required|integer|min:1|max:12|gte:minimo_grado',
                //'olimpiada_id' => 'required|exists:olimpiadas,id',
            ], [
                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.unique' => 'El nombre de la categoría ya existe.',
                'minimo_grado.required' => 'Debe indicar el grado mínimo.',
                'maximo_grado.required' => 'Debe indicar el grado máximo.',
                'maximo_grado.gte' => 'El grado máximo debe ser mayor o igual al mínimo.',
                //'olimpiada_id.required' => 'Debe seleccionar una olimpiada.',
                //'olimpiada_id.exists' => 'La olimpiada seleccionada no existe.',
            ]);

            // Crear la categoría
        $categoria = Categoria::create([
            'nombre' => $validatedData['nombre'],
            'minimo_grado' => $validatedData['minimo_grado'],
            'maximo_grado' => $validatedData['maximo_grado'],
        ]);

        // Asociar la categoría a la olimpiada
        //$categoria->olimpiadas()->attach($validatedData['olimpiada_id']);

        return response()->json([
            'message' => 'Categoría creada con éxito',
            'categoria' => $categoria
        ], 201);

            return response()->json(['message' => 'Categoría creada con éxito', 'categoria' => $categoria], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    // Modificar una categoría
    public function update(Request $request, $id)
    {
        try {
            $categoria = Categoria::findOrFail($id);

            $validatedData = $request->validate([
                'nombre' => 'sometimes|string|unique:categorias,nombre,' . $id,
                'minimo_grado' => 'sometimes|integer|min:1|max:12|lte:maximo_grado',
                'maximo_grado' => 'sometimes|integer|min:1|max:12|gte:minimo_grado',
            ]);

            $categoria->update($validatedData);

            return response()->json(['message' => 'Categoría actualizada', 'categoria' => $categoria]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }
    }

    // Eliminar una categoría
    public function destroy($id)
    {
        try {
            $categoria = Categoria::findOrFail($id);
            $categoria->delete();
            return response()->json(['message' => 'Categoría eliminada']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }
    }
}
