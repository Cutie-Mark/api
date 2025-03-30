<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

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
        $request->validate([
            'nombre' => 'required|string|unique:categorias,nombre',
            'minimo_grado' => 'required|integer|min:1|max:12',
            'maximo_grado' => 'required|integer|min:1|max:12|gte:minimo_grado',
        ]);

        $categoria = Categoria::create($request->all());

        return response()->json(['message' => 'Categoría creada con éxito', 'categoria' => $categoria], 201);
    }

    // Modificar una categoría
    public function update(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);

        $request->validate([
            'minimo_grado' => 'sometimes|integer|min:1|max:12|lte:maximo_grado',
            'maximo_grado' => 'sometimes|integer|min:1|max:12|gte:minimo_grado',
        ]);

        $categoria->update($request->all());

        return response()->json(['message' => 'Categoría actualizada', 'categoria' => $categoria]);
    }

    // Eliminar una categoría
    public function destroy($id)
    {
        $categoria = Categoria::findOrFail($id);
        $categoria->delete();

        return response()->json(['message' => 'Categoría eliminada']);
    }
}
