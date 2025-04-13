<?php

namespace App\Http\Controllers;

use App\Models\NivelCompetencia;
use App\Models\Area;
use App\Models\Categoria;
use Illuminate\Http\Request;
use App\Services\OlimpiadaService;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class NivelCompetenciaController extends Controller
{
    protected $olimpiadaService;

    public function __construct(OlimpiadaService $olimpiadaService)
    {
        $this->olimpiadaService = $olimpiadaService;
    }

    // 1. Obtener categorías por área y olimpiada
    public function getCategoriasByArea($areaId, $olimpiadaId)
    {
        $niveles = NivelCompetencia::where('area_id', $areaId)
            ->where('olimpiada_id', $olimpiadaId)
            ->with('categoria:id,nombre')
            ->get();

        return response()->json($niveles->pluck('categoria'));
    }

    // 2. Obtener áreas por categoría y olimpiada
    public function getAreasByCategoria($categoriaId, $olimpiadaId)
    {
        $niveles = NivelCompetencia::where('categoria_id', $categoriaId)
            ->where('olimpiada_id', $olimpiadaId)
            ->with('area:id,nombre')
            ->get();

        return response()->json($niveles->pluck('area'));
    }

    // 3. Obtener todas las categorías con sus áreas por olimpiada
    public function getAllCategoriasWithAreas($olimpiadaId)
    {
        $categorias = Categoria::with(['areas' => function ($query) use ($olimpiadaId) {
            $query->wherePivot('olimpiada_id', $olimpiadaId);
        }])->get();

        return response()->json($categorias);
    }

    // 4. Registrar una nueva relación área-categoría-olimpiada
    public function attach(Request $request)
    {
        try {
            if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden registrar nuevos niveles de competencia mientras hay un evento en curso.'], 400);
            }

            $validated = $request->validate([
                'area_id' => 'required|exists:areas,id',
                'categoria_id' => 'required|exists:categorias,id',
                'olimpiada_id' => 'required|exists:olimpiadas,id',
            ]);

            NivelCompetencia::firstOrCreate([
                'area_id' => $validated['area_id'],
                'categoria_id' => $validated['categoria_id'],
                'olimpiada_id' => $validated['olimpiada_id'],
            ], [
                'vigente' => true,
            ]);

            return response()->json(['message' => 'Nivel de competencia registrado con éxito.'], 201);

        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);  
        }
    }

    // 4. Eliminar una relación
    public function detach(Request $request)
    {
        try {
            if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden eliminar niveles de competencia mientras hay un evento en curso.'], 400);
            }

            $validated = $request->validate([
                'area_id' => 'required|exists:areas,id',
                'categoria_id' => 'required|exists:categorias,id',
                'olimpiada_id' => 'required|exists:olimpiadas,id',
            ]);

            NivelCompetencia::where($validated)->delete();

            return response()->json(['message' => 'Nivel de competencia eliminado.']);

        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);  
        }
    }

    // 5. Obtener todas las relaciones para una olimpiada
    public function getAllByOlimpiada($olimpiadaId)
    {
        $niveles = NivelCompetencia::with(['area:id,nombre', 'categoria:id,nombre'])
            ->where('olimpiada_id', $olimpiadaId)
            ->get();

        return response()->json($niveles);
    }

    // 6. Obtener áreas por curso y olimpiada
    public function getAreasByCurso($curso, $olimpiadaId)
    {
        $areaIds = NivelCompetencia::where('olimpiada_id', $olimpiadaId)
            ->whereHas('categoria', function ($query) use ($curso) {
                $query->where('minimo_grado', '<=', $curso)
                    ->where('maximo_grado', '>=', $curso);
            })
            ->pluck('area_id')
            ->unique();

        $areas = Area::whereIn('id', $areaIds)->get(['id', 'nombre']);
    
        return response()->json($areas);
    }

    
    // 7. Obtener categorías por curso y área en una olimpiada
    public function getCategoriasByAreaCurso($areaId, $curso, $olimpiadaId)
    {
        $categorias = Categoria::whereHas('niveles', function ($query) use ($areaId, $olimpiadaId) {
            $query->where('area_id', $areaId)
                  ->where('olimpiada_id', $olimpiadaId);
        })->where('minimo_grado', '<=', $curso)
          ->where('maximo_grado', '>=', $curso)
          ->get(['id', 'nombre']);

        return response()->json($categorias);
    }

    // 8. Obtener categorías por curso y olimpiada
    public function getCategoriasByCurso($curso, $olimpiadaId)
    {
        $categorias = Categoria::whereHas('niveles', function ($query) use ($olimpiadaId) {
            $query->where('olimpiada_id', $olimpiadaId);
        })->where('minimo_grado', '<=', $curso)
          ->where('maximo_grado', '>=', $curso)
          ->get(['id', 'nombre', 'minimo_grado', 'maximo_grado']);

        return response()->json($categorias);
    }

    // 9. Registrar múltiples áreas para una categoría en una olimpiada
    public function attachCategoriaToMultipleAreas(Request $request)
    {
        try {
            if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'Hay un evento en curso, espere a que finalice.'], 400);
            }

            $validated = $request->validate([
                'categoria_id' => 'required|exists:categorias,id',
                'olimpiada_id' => 'required|exists:olimpiadas,id',
                'area_ids' => 'required|array|min:1',
                'area_ids.*' => 'exists:areas,id',
            ]);

            foreach ($validated['area_ids'] as $areaId) {
                NivelCompetencia::firstOrCreate([
                    'categoria_id' => $validated['categoria_id'],
                    'area_id' => $areaId,
                    'olimpiada_id' => $validated['olimpiada_id'],
                ], ['vigente' => true]);
            }

            return response()->json(['message' => 'Categoría vinculada a múltiples áreas con éxito'], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->errors())->flatten()->all()], 422);
        }
    }

    // Area por olimpiadas
    public function getAreasByOlimpiada($olimpiadaId)
    {
        try {
            $areaIds = NivelCompetencia::where('olimpiada_id', $olimpiadaId)
                        ->distinct()
                        ->pluck('area_id');

            $areas = Area::whereIn('id', $areaIds)->get(['id', 'nombre']);

            return response()->json($areas);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener las áreas.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAllAreasWithCategorias($olimpiadaId)
    {
        // Obtenemos todas las combinaciones únicas de áreas y categorías para la olimpiada
        $niveles = NivelCompetencia::with(['area:id,nombre', 'categoria:id,nombre'])
            ->where('olimpiada_id', $olimpiadaId)
            ->get();

        // Agrupamos las categorías por área
        $resultado = $niveles->groupBy('area.id')->map(function ($items) {
            $area = $items->first()->area;
            $categorias = $items->pluck('categoria')->unique('id')->values();
            return [
                'id' => $area->id,
                'nombre' => $area->nombre,
                'categorias' => $categorias,
            ];
        })->values();

        return response()->json($resultado);
    }


}
