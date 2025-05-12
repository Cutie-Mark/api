<?php

namespace App\Http\Controllers;

use App\Models\NivelCompetencia;
use App\Models\Area;
use App\Models\Categoria;
use Illuminate\Http\Request;
use App\Services\OlimpiadaService;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

use App\Models\Olimpiada;
use Illuminate\Support\Facades\Log;


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
        $niveles = NivelCompetencia::where('olimpiada_id', $olimpiadaId)
            ->join('categorias', 'niveles_competencia.categoria_id', '=', 'categorias.id')
            ->join('areas', 'niveles_competencia.area_id', '=', 'areas.id')
            ->select(
                'categorias.id as categoria_id',
                'categorias.nombre as categoria_nombre',
                'categorias.minimo_grado',
                'categorias.maximo_grado',
                'areas.id as area_id',
                'areas.nombre as area_nombre'
            )
            ->orderBy('categorias.id')
            ->get();

        $resultado = [];

        foreach ($niveles as $nivel) {
            $categoriaId = $nivel->categoria_id;

            if (!isset($resultado[$categoriaId])) {
                $resultado[$categoriaId] = [
                    'id' => $categoriaId,
                    'nombre' => $nivel->categoria_nombre,
                    'minimo_grado' => $nivel->minimo_grado,
                    'maximo_grado' => $nivel->maximo_grado,
                    'areas' => [],
                ];
            }

            // Evitar áreas duplicadas si existen
            if (!in_array($nivel->area_id, array_column($resultado[$categoriaId]['areas'], 'id'))) {
                $resultado[$categoriaId]['areas'][] = [
                    'id' => $nivel->area_id,
                    'nombre' => $nivel->area_nombre,
                ];
            }
        }

        return response()->json(array_values($resultado));
    }

    // 4. Registrar una nueva relación área-categoría-olimpiada
    public function attach(Request $request)
    {
        try {
            /*if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden registrar nuevos niveles de competencia mientras hay una olimpiada en curso.'], 400);
            }*/

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

            return response()->json(['message' => 'Se asociaron las categorías correctamente'], 201);

        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);  
        }
    }

    // 4. Eliminar una relación
    public function detach(Request $request)
    {
        try {
           /* if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden eliminar niveles de competencia mientras hay una olimpiada en curso.'], 400);
            }*/

            $validated = $request->validate([
                'area_id' => 'required|exists:areas,id',
                'categoria_id' => 'required|exists:categorias,id',
                'olimpiada_id' => 'required|exists:olimpiadas,id',
            ]);

            NivelCompetencia::where($validated)->delete();

            return response()->json(['message' => 'Se suspendio la asociacion de las categorías correctamente']);

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
        $niveles = NivelCompetencia::where('olimpiada_id', $olimpiadaId)
            ->join('categorias', 'niveles_competencia.categoria_id', '=', 'categorias.id')
            ->join('areas', 'niveles_competencia.area_id', '=', 'areas.id')
            ->where('categorias.minimo_grado', '<=', $curso)
            ->where('categorias.maximo_grado', '>=', $curso)
            ->select(
                'categorias.id as categoria_id',
                'categorias.nombre as categoria_nombre',
                'categorias.minimo_grado',
                'categorias.maximo_grado',
                'areas.id as area_id',
                'areas.nombre as area_nombre'
            )
            ->orderBy('categorias.id')
            ->get();

        $resultado = [];

        foreach ($niveles as $nivel) {
            $categoriaId = $nivel->categoria_id;

            if (!isset($resultado[$categoriaId])) {
                $resultado[$categoriaId] = [
                    'id' => $categoriaId,
                    'nombre' => $nivel->categoria_nombre,
                    'minimo_grado' => $nivel->minimo_grado,
                    'maximo_grado' => $nivel->maximo_grado,
                    'areas' => [],
                ];
            }

            if (!in_array($nivel->area_id, array_column($resultado[$categoriaId]['areas'], 'id'))) {
                $resultado[$categoriaId]['areas'][] = [
                    'id' => $nivel->area_id,
                    'nombre' => $nivel->area_nombre,
                ];
            }
        }

        return response()->json(array_values($resultado));
    }


    // 9. Registrar múltiples áreas para una categoría en una olimpiada
    public function attachCategoriaToMultipleAreas(Request $request)
    {
        try {
            /*if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'Hay una olimpiada en curso, espere a que finalice.'], 400);
            }*/

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

            return response()->json(['message' => 'Se asociaron las categorías correctamente'], 201);
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
        $niveles = NivelCompetencia::with(['area:id,nombre', 'categoria:id,nombre'])
            ->where('olimpiada_id', $olimpiadaId)
            ->get();

        $resultado = $niveles->groupBy('area.id')->map(function ($items) {
            $area = $items->first()->area;
            $categorias = $items->pluck('categoria')
                                ->filter()
                                ->unique('id')
                                ->values();
            return [
                'id' => $area->id,
                'nombre' => $area->nombre,
                'categorias' => $categorias,
            ];
        })->values();

        return response()->json($resultado);
    }

    public function deactivate(Request $request)
    {
        try {
            /*if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden desactivar niveles de competencia mientras hay un evento en curso.'], 400);
            }*/

            $validated = $request->validate([
                'area_id' => 'required|exists:areas,id',
                'categoria_id' => 'required|exists:categorias,id',
                'olimpiada_id' => 'required|exists:olimpiadas,id',
            ]);

            $nivel = NivelCompetencia::where($validated)->first();

            if (!$nivel) {
                return response()->json(['error' => 'Nivel de competencia no encontrado.'], 404);
            }

            $nivel->vigente = false;
            $nivel->save();

            return response()->json(['message' => 'Nivel de competencia desactivado correctamente.']);

        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo desactivar el nivel de competencia. Intente nuevamente.'], 500);
        }
    }

    public function activate(Request $request)
    {
        try {
            /*if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden desactivar niveles de competencia mientras hay un evento en curso.'], 400);
            }*/

            $validated = $request->validate([
                'area_id' => 'required|exists:areas,id',
                'categoria_id' => 'required|exists:categorias,id',
                'olimpiada_id' => 'required|exists:olimpiadas,id',
            ]);

            $nivel = NivelCompetencia::where($validated)->first();

            if (!$nivel) {
                return response()->json(['error' => 'Nivel de competencia no encontrado.'], 404);
            }

            $nivel->vigente = true;
            $nivel->save();

            return response()->json(['message' => 'Nivel de competencia activado correctamente.']);

        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo activar el nivel de competencia. Intente nuevamente.'], 500);
        }
    }

    public function getSortCategoriasByOlimpiada($id)
    {
        try {

            $olimpiada = Olimpiada::findOrFail($id);
            $categorias = $olimpiada->categorias()->orderBy('minimo_grado')->get();
            $maximoGrado = $categorias->max('maximo_grado');
            $resultado = array_fill(0, $maximoGrado, []);
            foreach ($categorias as $categoria) {
                for ($grado = $categoria->minimo_grado; $grado <= $categoria->maximo_grado; $grado++) {
                    $resultado[$grado - 1][] = [
                        'id' => $categoria->id,
                        'nombre' => $categoria->nombre,
                        'minimo_grado' => $categoria->minimo_grado,
                        'maximo_grado' => $categoria->maximo_grado,
                    ];
                }
            }
            return response()->json($resultado, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Olimpiada no encontrada'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al recuperar las categorías'], 500);
        }
    }


    // Sincronizar asociaciones

    public function syncCategorias(Request $request)
    {
        $validated = $request->validate([
            'id_area'      => 'required|exists:areas,id',
            'id_olimpiada' => 'required|exists:olimpiadas,id',
            'agregar'      => 'array',
            'agregar.*'    => 'integer|exists:categorias,id',
            'quitar'       => 'array',
            'quitar.*'     => 'integer|exists:categorias,id',
        ]);

        $idArea      = $validated['id_area'];
        $idOlimpiada = $validated['id_olimpiada'];
        $agregar     = $validated['agregar'] ?? [];
        $quitar      = $validated['quitar'] ?? [];

        $agregadasExito = [];
        $eliminadasExito = [];

        // Agregar relaciones
        foreach ($agregar as $categoriaId) {
            try {
                $registro = NivelCompetencia::firstOrCreate([
                    'area_id' => $idArea,
                    'categoria_id' => $categoriaId,
                    'olimpiada_id' => $idOlimpiada,
                ], [
                    'vigente' => true
                ]);

                // Solo si se creó nuevo
                if ($registro->wasRecentlyCreated) {
                    $agregadasExito[] = $categoriaId;
                }
            } catch (\Exception $e) {
                // Puedes loguear esto si querés para auditoría
                \Log::warning("No se pudo agregar: Area $idArea, Categoria $categoriaId, Olimpiada $idOlimpiada");
            }
        }

        // Eliminar relaciones
        foreach ($quitar as $categoriaId) {
            try {
                $eliminado = NivelCompetencia::where([
                    'area_id' => $idArea,
                    'categoria_id' => $categoriaId,
                    'olimpiada_id' => $idOlimpiada
                ])->delete();

                if ($eliminado) {
                    $eliminadasExito[] = $categoriaId;
                }
            } catch (\Exception $e) {
                \Log::warning("No se pudo eliminar: Area $idArea, Categoria $categoriaId, Olimpiada $idOlimpiada");
            }
        }

        return response()->json([
            'message' => 'Se asociaron las categorías exitosamente',
            'agregadas' => $agregadasExito,
            'eliminadas' => $eliminadasExito
        ]);
    }


    public function detachByOlimpiadaAndArea(Request $request)
    {
        try {
            /*if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden eliminar niveles de competencia mientras hay una olimpiada en curso.'], 400);
            }*/

            $validated = $request->validate([
                'area_id' => 'required|exists:areas,id',
                'olimpiada_id' => 'required|exists:olimpiadas,id',
            ]);

            NivelCompetencia::where('area_id', $validated['area_id'])
                ->where('olimpiada_id', $validated['olimpiada_id'])
                ->delete();

            return response()->json(['message' => 'Se eliminaron correctamente todos los niveles de competencia para el área y olimpiada especificados.']);

        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);  
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo completar la eliminación. Intente nuevamente.'], 500);
        }
    }
    public function attachAreaOlimpiada(Request $request)
    {
        try {
            /*if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden registrar nuevos niveles de competencia mientras hay una olimpiada en curso.'], 400);
            }*/

            $validated = $request->validate([
                'area_id' => 'required|exists:areas,id',
                //'categoria_id' => 'required|exists:categorias,id',
                'olimpiada_id' => 'required|exists:olimpiadas,id',
            ]);

            NivelCompetencia::firstOrCreate([
                'area_id' => $validated['area_id'],
                'categoria_id' => null,
                'olimpiada_id' => $validated['olimpiada_id'],
            ], [
                'vigente' => true,
            ]);

            return response()->json(['message' => 'Área asociada a olimpiada correctamente'], 201);

        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);  
        }
    }

}
