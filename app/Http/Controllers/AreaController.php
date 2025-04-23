<?php

namespace App\Http\Controllers;

use App\Services\OlimpiadaService;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AreaController extends Controller
{

    protected $olimpiadaService;

    public function __construct(OlimpiadaService $olimpiadaService)
    {
        $this->olimpiadaService = $olimpiadaService;
    }

    // Obtener todas las áreas
    public function index()
    {
        return response()->json(Area::all());
    }

    public function find(Request $request)
    {
        $nombre = $request->query('nombre');

        if ($nombre) {
            $areas = Area::where('nombre', 'ILIKE', "%$nombre%")->get();
        } else {
            $areas = Area::all();
        }

        return response()->json($areas);
    }

    
    // Guardar un área
    public function store(Request $request)
    {
        try {

            /*if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden registrar areas nuevas, Hay un evento en curso, espere a que finalice.'], 400);
            }*/

            $validatedData = $request->validate([
                'nombre' => 'required|string|max:40',
            ], [
                'nombre.required' => 'El nombre del área de competencia es obligatorio.',
            ]);

            $nombreIngresado = $validatedData['nombre'];
            $nombreNormalizado = $this->normalizarTexto($nombreIngresado);

            $areas = Area::select('nombre')->get()->pluck('nombre');

            $existe = $areas->contains(function ($nombre) use ($nombreNormalizado) {
                return $this->normalizarTexto($nombre) === $nombreNormalizado;
            });

            if ($existe) {
            return response()->json(['error' => 'El área ya fue registrada con anterioridad. Intente con otra.'], 422);
            }

            $area = Area::create([
                'nombre' => $nombreNormalizado
            ]);

            // Asociar el área a la olimpiada
            //$area->olimpiadas()->attach($validatedData['olimpiada_id']);

            return response()->json([
                'message' => 'El área de competencia se creó correctamente.',
                'area' => $area
            ], 201);

        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);  
        } catch (Exception $e) {
            Log::error('Error al guardar el área: ' . $e->getMessage());
            return response()->json(['error' => 'El area no se guardó, intente de nuevo.'], 500);

        }
    }

    // 4. Eliminar un área por ID
    public function destroy($id)
    {
        try {
            /*if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se puede eliminar el área. Hay un evento en curso, espere a que finalice.'], 400);
            }*/

            $area = Area::findOrFail($id);
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
            /*if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se puede desactivar el área. Hay un evento en curso, espere a que finalice.'], 400);
            }*/

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

            return response()->json(['message' => 'Área activada correctamente.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Área no encontrada.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo activar el área. Intente nuevamente.'], 500);
        }
    }


    private function normalizarTexto($text)
    {
        $upper = mb_strtoupper($text, 'UTF-8');

        // Normaliza quitando tildes (pero no elimina la Ñ)
        $sinTildes = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú'],
            ['A', 'E', 'I', 'O', 'U'],
            $upper
        );

        return $sinTildes;
    }
}