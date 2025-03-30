<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Categoria;
use Illuminate\Http\Request;
use App\Models\AreaCategoria;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AreaCategoriaController extends Controller
{
    // 1. Obtener todas las categorías relacionadas a un área por su ID (incluye el área)
    public function findCategoriasByArea($areaId)
    {
        $area = Area::with('categorias')->find($areaId);

        if (!$area) {
            return response()->json(['error' => 'Área no encontrada'], 404);
        }

        return response()->json($area);
    }

    // 2. Obtener todas las áreas con sus categorías (sin importar repeticiones)
    public function getAllAreasWithCategorias()
    {
        $areas = Area::with('categorias')->get();
        return response()->json($areas);
    }

    // 3. Registrar una relación en la tabla intermedia
    public function attachCategoriaToArea(Request $request)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'categoria_id' => 'required|exists:categorias,id',
        ]);

        $area = Area::find($request->area_id);
        $area->categorias()->attach($request->categoria_id);

        return response()->json(['message' => 'Relación creada con éxito'], 201);
    }

    // 4. Eliminar una relación en la tabla intermedia
    public function detachCategoriaFromArea(Request $request)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'categoria_id' => 'required|exists:categorias,id',
        ]);

        $area = Area::find($request->area_id);
        $area->categorias()->detach($request->categoria_id);

        return response()->json(['message' => 'Relación eliminada con éxito']);
    }
}
