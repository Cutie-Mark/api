<?php

namespace App\Http\Controllers;

use App\Services\OlimpiadaService;
use App\Models\Area;
use App\Models\Categoria;
use Illuminate\Http\Request;
use App\Models\AreaCategoria;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;


class AreaCategoriaController extends Controller
{
    protected $olimpiadaService;

    public function __construct(OlimpiadaService $olimpiadaService)
    {
        $this->olimpiadaService = $olimpiadaService;
    }
    // 1. Obtener todas las categorías relacionadas a un área por su ID (incluye el área)
    public function findCategoriasByArea($areaId)
    {
        try {
            $area = Area::with('categorias')->findOrFail($areaId);
            
            // Agrupar las categorías en un array
            $area->categorias = $area->categorias->pluck('id');
            
            return response()->json($area);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Área no encontrada'], 404);
        }
    }

    // 2. Obtener las áreas con sus categorías 
    public function getAllAreasWithCategorias()
    {
        $areas = Area::with('categorias')->get();
        return response()->json($areas);
    }

    // 3. Obtener las categorías con sus áreas
    public function getAllCategoriasWithAreas()
    {
        $categorias = Categoria::with('areas')->get();
        return response()->json($categorias);
    }

    // 4. Registrar una relación en la tabla intermedia
    public function attachCategoriaToArea(Request $request)
    {
        try {
            if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden registrar areas nuevas, Hay un evento en curso, espere a que finalice.'], 400);
            }
            $validatedData = $request->validate([
                'area_id' => 'required|exists:areas,id',
                'categoria_id' => 'required|exists:categorias,id',
            ]);

            $area = Area::findOrFail($validatedData['area_id']);
            $area->categorias()->attach($validatedData['categoria_id']);

            return response()->json(['message' => 'Relación creada con éxito'], 201);
        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);  
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Área no encontrada'], 404);
        }
    }

    // 5. Eliminar una relación en la tabla intermedia
    public function detachCategoriaFromArea(Request $request)
    {
        try {
            if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden registrar areas nuevas, Hay un evento en curso, espere a que finalice.'], 400);
            }
            $validatedData = $request->validate([
                'area_id' => 'required|exists:areas,id',
                'categoria_id' => 'required|exists:categorias,id',
            ]);

            $area = Area::findOrFail($validatedData['area_id']);
            $area->categorias()->detach($validatedData['categoria_id']);

            return response()->json(['message' => 'Relación eliminada con éxito']);
        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);  
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Área no encontrada'], 404);
        }
    }


    // 6. Obtener las areas por curso dado

    public function getAreasByCurso($curso)
    {
        $areas = Area::whereHas('categorias', function ($query) use ($curso) {
            $query->where('minimo_grado', '<=', $curso)
                ->where('maximo_grado', '>=', $curso);
        })->get(['id', 'nombre']); 

        return response()->json($areas);
    }

    // 7. Obtener las categorias por curso y area dados

    public function getCategoriasByAreaCurso($areaId, $curso)
    {
        $categorias = Categoria::whereHas('areas', function ($query) use ($areaId) {
                                                    $query->where ('areas.id', $areaId);
        })->where('minimo_grado', '<=', $curso)
          ->where('maximo_grado', '>=', $curso)
          ->select('id','nombre')
          ->get();

        return response()->json($categorias);
    }

    public function getCategoriasByCurso($curso)
    {
        $categorias = Categoria::with('areas')
            ->where('minimo_grado', '<=', $curso)
            ->where('maximo_grado', '>=', $curso)
            ->get(['id', 'nombre', 'minimo_grado', 'maximo_grado']); // seleccionar columnas necesarias

        return response()->json($categorias);
    }


    public function attachCategoriaToMultipleAreas(Request $request)
    {
        try {
            if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden registrar areas nuevas, Hay un evento en curso, espere a que finalice.'], 400);
            }
            $validatedData = $request->validate([
                'categoria_id' => 'required|exists:categorias,id',
                'area_ids' => 'required|array|min:1',
                'area_ids.*' => 'exists:areas,id',
            ]);

            $categoria = Categoria::findOrFail($validatedData['categoria_id']);
            $categoria->areas()->attach($validatedData['area_ids']);

            return response()->json(['message' => 'Categoría vinculada a múltiples áreas con éxito'], 201);
        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);  
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }
    }

    public function attachMultipleCategoriasToArea(Request $request)
    {
        try {
            if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden registrar areas nuevas, Hay un evento en curso, espere a que finalice.'], 400);
            }
            $validatedData = $request->validate([
                'area_id' => 'required|exists:areas,id',
                'categoria_ids' => 'required|array|min:1',
                'categoria_ids.*' => 'exists:categorias,id',
            ]);

            $area = Area::findOrFail($validatedData['area_id']);
            $area->categorias()->attach($validatedData['categoria_ids']);

            return response()->json(['message' => 'Área vinculada a múltiples categorías con éxito'], 201);
        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);  
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Área no encontrada'], 404);
        }
    }








}
