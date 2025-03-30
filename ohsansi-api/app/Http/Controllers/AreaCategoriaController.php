<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Categoria;
use Illuminate\Http\Request;
use App\Models\AreaCategoria;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;


class AreaCategoriaController extends Controller
{
    // 1. Obtener todas las categorías relacionadas a un área por su ID (incluye el área)
    public function findCategoriasByArea($areaId)
    {
        try {
            $area = Area::with('categorias')->findOrFail($areaId);
            return response()->json($area);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Área no encontrada'], 404);
        }
    }

    // 2. Obtener todas las áreas con sus categorías (sin importar repeticiones)
    public function getAllAreasWithCategorias()
    {
        $areas = Area::with('categorias')->get();
        return response()->json($areas);
    }

    // 2. Obtener todas las categorías con sus áreas (sin importar repeticiones)
    public function getAllCategoriasWithAreas()
    {
        $categorias = Categoria::with('areas')->get();
        return response()->json($categorias);
    }

    // 4. Registrar una relación en la tabla intermedia
    public function attachCategoriaToArea(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'area_id' => 'required|exists:areas,id',
                'categoria_id' => 'required|exists:categorias,id',
            ]);

            $area = Area::findOrFail($validatedData['area_id']);
            $area->categorias()->attach($validatedData['categoria_id']);

            return response()->json(['message' => 'Relación creada con éxito'], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Área no encontrada'], 404);
        }
    }

    // 5. Eliminar una relación en la tabla intermedia
    public function detachCategoriaFromArea(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'area_id' => 'required|exists:areas,id',
                'categoria_id' => 'required|exists:categorias,id',
            ]);

            $area = Area::findOrFail($validatedData['area_id']);
            $area->categorias()->detach($validatedData['categoria_id']);

            return response()->json(['message' => 'Relación eliminada con éxito']);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Área no encontrada'], 404);
        }
    }
}
