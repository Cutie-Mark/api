<?php

namespace App\Http\Controllers;

use App\Http\Resources\AreaResource;
use App\Models\Area;
use App\Services\AreaService;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Exception;

class AreaController extends Controller
{

    protected $areaService;

    public function __construct(AreaService $areaService)
    {
        $this->areaService = $areaService;
    }

    // Obtener todas las áreas
    public function listar()
    {
        return Area::all();
    }

    public function buscar(Request $request)
    {
        $nombre = $request->query('nombre');

        $areas = $nombre
            ? Area::where('nombre', 'ILIKE', "%$nombre%")->get()
            : Area::all();

        return $areas;
    }

    
    // Guardar un área
    public function guardar(Request $request)
    {
        try {

            $request->validate([
                'nombre' => 'required|string|max:40'
            ], [
                'nombre.required' => 'El nombre del área de competencia es obligatorio.'
            ]);

            $area = $this->areaService->crear($request->input('nombre'));

            if ($area === 'duplicado') {
                return response()->json(['error' => 'El área ya fue registrada. Intente con otra.'], 422);
            }

            return response()->json([
                'message' => 'El área de competencia se creó correctamente',
                'area' => $area
            ], 201);

        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);  
        } catch (Exception $e) {
            return response()->json(['error' => 'El area no se guardó, intente de nuevo.'], 500);

        }
    }

    // 4. Eliminar un área por ID
    public function eliminar($id)
    {
        try {
            $area = $this->areaService->eliminar($id);

            if ($area === 'usada') {
                return response()->json(['error' => 'No se puede eliminar el área porque ya esta en uso en una olimpiada.'], 400);
            }

            return response()->json(['message' => 'El área de competencia se eliminó correctamente.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Área no encontrada'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Hubo un error al eliminar el área, intente de nuevo.'], 500);
        }
    }

    // Desactivar un área 
    public function desactivar($id)
    {
        try {
            $this->areaService->alternarEstado($id, false);

            return response()->json(['message' => 'Área desactivada correctamente.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Área no encontrada.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo desactivar el área. Intente nuevamente.'], 500);
        }
    }

    public function activar($id)
    {
        try {
            $this->areaService->alternarEstado($id, true);

            return response()->json(['message' => 'Se habilitó el área ']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Área no encontrada.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo activar el área. Intente nuevamente.'], 500);
        }
    }
}