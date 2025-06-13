<?php

namespace App\Http\Controllers;

use App\Http\Resources\AreaResource;
use App\Models\Area;
use App\Services\TextoService;
use App\Services\OlimpiadaService;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Exception;

class AreaController extends Controller
{

    protected $olimpiadaService;
    protected $textoService;

    public function __construct(OlimpiadaService $olimpiadaService, TextoService $textoService)
    {
        $this->olimpiadaService = $olimpiadaService;
        $this->textoService = $textoService;
    }

    // Obtener todas las áreas
    public function index()
    {
        return Area::all();
    }

    public function find(Request $request)
    {
        $nombre = $request->query('nombre');

        $areas = $nombre
            ? Area::where('nombre', 'ILIKE', "%$nombre%")->get()
            : Area::all();

        return $areas;
    }

    
    // Guardar un área
    public function store(Request $request)
    {
        try {

            $validatedData = $request->validate([
                'nombre' => 'required|string|max:40',
            ], [
                'nombre.required' => 'El nombre del área de competencia es obligatorio.',
            ]);

            $nombreIngresado = $validatedData['nombre'];
            $upper = mb_strtoupper($nombreIngresado, 'UTF-8');
            $nombreNormalizado = $this->textoService->normalizar($upper);

            $existe = Area::get()->contains(fn($area) => 
                $this->textoService->normalizar($area->nombre) === $nombreNormalizado
            );


            if ($existe) {
            return response()->json(['error' => 'El área ya fue registrada con anterioridad. Intente con otra.'], 422);
            }

            $area = Area::create([
                'nombre' => $nombreNormalizado
            ]);


            return response()->json([
                'message' => 'El área de competencia se creó correctamente.',
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
    public function destroy($id)
    {
        try {

            $area = Area::findOrFail($id);
            if ($area->categorias()->exists() || $area->olimpiadas()->exists()) {
                return response()->json([
                    'error' => 'No se puede eliminar el área porque ya esta en uso en una olimpiada.'
                ], 400);
            }
            
            $area->delete();
            return response()->json(['message' => 'El área de competencia se eliminó correctamente.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Área no encontrada'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Hubo un error al eliminar el área, intente de nuevo.'], 500);
        }
    }

    // Desactivar un área 
    public function deactivate($id)
    {
        try {

            $area = Area::findOrFail($id);

            $area->vigente = false;
            $area->save();

            return response()->json(['message' => 'Área desactivada correctamente.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Área no encontrada.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo desactivar el área. Intente nuevamente.'], 500);
        }
    }

    public function activate($id)
    {
        try {
            /*if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se puede desactivar el área. Hay un evento en curso, espere a que finalice.'], 400);
            }*/

            $area = Area::findOrFail($id);

            $area->vigente = true;
            $area->save();

            return response()->json(['message' => 'Se habilitó el área ']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Área no encontrada.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo activar el área. Intente nuevamente.'], 500);
        }
    }
}