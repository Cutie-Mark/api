<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AreaOlimpiada;
use App\Models\Area;
use App\Services\OlimpiadaService;

class AreaOlimpiadaController extends Controller
{
    protected $olimpiadaService;

    public function __construct(OlimpiadaService $olimpiadaService)
    {
        $this->olimpiadaService = $olimpiadaService;
    }

    // Crear una relación área-olimpiada
    public function store(Request $request)
    {
        try {
            if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden registrar areas nuevas, Hay un evento en curso, espere a que finalice.'], 400);
            }
            $request->validate([
                'area_id' => 'required|exists:areas,id',
                'olimpiada_id' => 'required|exists:olimpiadas,id',
            ]);

            $registro = AreaOlimpiada::create([
                'area_id' => $request->area_id,
                'olimpiada_id' => $request->olimpiada_id,
            ]);

            return response()->json($registro, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al registrar área en la olimpiada.', 'error' => $e->getMessage()], 500);
        }
    }

    // Eliminar una relación área-olimpiada
    public function destroy(Request $request)
    {
        try {
            if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden registrar areas nuevas, Hay un evento en curso, espere a que finalice.'], 400);
            }
            $validatedData = $request->validate([
                'area_id' => 'required|exists:areas,id',
                'olimpiada_id' => 'required|exists:olimpiadas,id',
            ]);

            $area = Area::findOrFail($validatedData['area_id']);
            $area->olimpiadas()->detach($validatedData['olimpiada_id']);


            return response()->json(['message' => 'Registro eliminado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el registro.', 'error' => $e->getMessage()], 500);
        }
    }

    // Obtener  áreas asociadas a una olimpiada
    public function getAreasByOlimpiada($id)
    {
        try {
            $areas = Area::whereHas('olimpiadas', function ($query) use ($olimpiada_id) {
                $query->where('olimpiadas.id', $olimpiada_id);
            })->get();

            return response()->json($areas, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener las áreas.', 'error' => $e->getMessage()], 500);
        }
    }
}
