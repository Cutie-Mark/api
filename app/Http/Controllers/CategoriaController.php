<?php

namespace App\Http\Controllers;

use App\Services\OlimpiadaService;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;


class CategoriaController extends Controller
{

    protected $olimpiadaService;

    public function __construct(OlimpiadaService $olimpiadaService)
    {
        $this->olimpiadaService = $olimpiadaService;
    }

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

    public function find(Request $request)
    {
        $nombre = $request->query('nombre');

        if ($nombre) {
            $categorias = Categoria::where('nombre', 'ILIKE', "%$nombre%")->get();
        } else {
            $categorias = Categoria::all();
        }

        return response()->json($categorias);
    }

    // Registrar una nueva categoría
    public function store(Request $request)
    {
        try {
            if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden registrar nuevos niveles de competencia, Hay un evento en curso, espere a que finalice.'], 400);
            }
            $validatedData = $request->validate([
                'nombre' => 'required|string|unique:categorias,nombre',
                'minimo_grado' => 'required|integer|min:1|max:12',
                'maximo_grado' => 'required|integer|min:1|max:12|gte:minimo_grado',
                //'olimpiada_id' => 'required|exists:olimpiadas,id',
            ], [
                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.unique' => 'Este nombre de nivel de competencia ya existe. Intente con otro.',
                'minimo_grado.required' => 'Debe indicar el grado mínimo.',
                'maximo_grado.required' => 'Debe indicar el grado máximo.',
                //'maximo_grado.gte' => 'El grado máximo debe ser mayor o igual al mínimo.',
                //'olimpiada_id.required' => 'Debe seleccionar una olimpiada.',
                //'olimpiada_id.exists' => 'La olimpiada seleccionada no existe.',
            ]);

            // Convertir el nombre a mayúsculas
            $nombreMayus = strtoupper($validatedData['nombre']);

            // Validar que no exista una categoría con el mismo nombre (sin importar mayúsculas)
            if (Categoria::whereRaw('UPPER(nombre) = ?', [$nombreMayus])->exists()) {
                return response()->json(['error' => 'Este nombre de nivel de competencia ya existe. Intente con otro.'], 422);
            }

            // Crear la categoría
            $categoria = Categoria::create([
                'nombre' => $validatedData['nombre'],
                'minimo_grado' => $validatedData['minimo_grado'],
                'maximo_grado' => $validatedData['maximo_grado'],
            ]);

            // Asociar la categoría a la olimpiada
            //$categoria->olimpiadas()->attach($validatedData['olimpiada_id']);

            return response()->json([
                'message' => 'El nivel de competencia se registró correctamente.',
                'categoria' => $categoria
            ], 201);
        } catch (ValidationException $e) {
            if (isset($e->errors()['nombre']) && in_array('unique', $e->errors()['nombre'])) {
                return response()->json(['error' => 'Este nombre de nivel de competencia ya existe. Intente con otro.'], 422);
            }
        // Mensaje genérico para otros errores de validación
            return response()->json(['error' => 'No se pudo registrar el nivel de competencia. Intente nuevamente.'], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo registrar el nivel de competencia. Intente nuevamente.'], 500);
        }
    }

    // Modificar una categoría
    public function update(Request $request, $id)
    {
        try {

            if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se puede modificar el nivel de competencia, Hay un evento en curso, espere a que finalice.'], 400);
            }

            $categoria = Categoria::findOrFail($id);

            $validatedData = $request->validate([
                'minimo_grado' => 'sometimes|integer|min:1|max:12|lte:maximo_grado',
                'maximo_grado' => 'sometimes|integer|min:1|max:12|gte:minimo_grado',
            ]);

            $categoria->update($validatedData);

            return response()->json(['message' => 'La edición se realizó correctamente.', 'categoria' => $categoria]);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'La edición no se guardó, inténtelo de nuevo.'], 500);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'La edición no se guardó, inténtelo de nuevo.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'La edición no se guardó, inténtelo de nuevo.'], 500);
        }
    }

    // Eliminar una categoría
    public function destroy($id)
    {
        try {
            if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se puede eliminar el nivel de competencia. Hay un evento en curso, espere a que finalice.'], 400);
            }

            $categoria = Categoria::findOrFail($id);
            $categoria->delete();
            return response()->json(['message' => 'El nivel de competencia se eliminó correctamente.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Hubo un error al eliminar el nivel de competencia, intente de nuevo.'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Hubo un error al eliminar el nivel de competencia, intente de nuevo.'], 500);
        }
    }
}
