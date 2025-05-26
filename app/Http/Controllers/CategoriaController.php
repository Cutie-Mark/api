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

            $validatedData = $request->validate([
                'nombre' => 'required|string|unique:categorias,nombre',
                'minimo_grado' => 'required|integer|min:1|max:12',
                'maximo_grado' => 'required|integer|min:1|max:12|gte:minimo_grado',
            ], [
                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.unique' => 'Este nombre de nivel de competencia ya existe. Intente con otro.',
                'minimo_grado.required' => 'Debe indicar el grado mínimo.',
                'maximo_grado.required' => 'Debe indicar el grado máximo.',
            ]);

            // Convertir el nombre a mayúsculas
            $nombreMayus = strtoupper($validatedData['nombre']);

            // Validar que no exista una categoría con el mismo nombre (sin importar mayúsculas)
            if (Categoria::whereRaw('UPPER(nombre) = ?', [$nombreMayus])->exists()) {
                return response()->json(['error' => 'Esta categoría ya existe. Intente con otra.'], 422);
            }

            // Crear la categoría
            $categoria = Categoria::create([
                'nombre' => $validatedData['nombre'],
                'minimo_grado' => $validatedData['minimo_grado'],
                'maximo_grado' => $validatedData['maximo_grado'],
            ]);

            return response()->json([
                'message' => 'La categoría se registró correctamente.',
                'categoria' => $categoria
            ], 201);
        } catch (ValidationException $e) {
            if (isset($e->errors()['nombre']) && in_array('unique', $e->errors()['nombre'])) {
                return response()->json(['error' => 'Esta categoria ya existe. Intente con otro.'], 422);
            }
            return response()->json(['error' => 'No se pudo registrar la categoría. Intente nuevamente.'], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo registrar la categoría. Intente nuevamente.'], 500);
        }
    }

    // Modificar una categoría
    public function update(Request $request, $id)
    {
        try {

            $categoria = Categoria::findOrFail($id);

            $validatedData = $request->validate([
                'minimo_grado' => 'sometimes|integer|min:1|max:12|lte:maximo_grado',
                'maximo_grado' => 'sometimes|integer|min:1|max:12|gte:minimo_grado',
            ]);

            $categoria->update($validatedData);

            return response()->json(['message' => 'Categoría editada correctamente.', 'categoria' => $categoria]);
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

            $categoria = Categoria::findOrFail($id);
            if ($categoria->niveles_competencia()->exists() || $categoria->olimpiadas()->exists()) {
                return response()->json([
                    'error' => 'No se puede eliminar la categoria porque ya esta en uso en una olimpiada.'
                ], 400);
            }
            $categoria->delete();
            return response()->json(['message' => 'La categoría se eliminó correctamente.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Hubo un error al eliminar la categoría, intente de nuevo.'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Hubo un error al eliminar la categoría, intente de nuevo.'], 500);
        }
    }

    public function deactivate($id)
    {
        try {
            /*if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se puede desactivar la categoría. Hay un evento en curso.'], 400);
            }*/

            $categoria = Categoria::findOrFail($id);
            $categoria->vigente = false;
            $categoria->save();

            return response()->json(['message' => 'Se dio de baja la categoría']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Categoría no encontrada.'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al dar de baja la categoría'], 500);
        }
    }

    public function activate($id)
    {
        try {
            $categoria = Categoria::findOrFail($id);
            $categoria->vigente = true;
            $categoria->save();

            return response()->json(['message' => 'Se activo la categoría']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Categoría no encontrada.'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al activar la categoría'], 500);
        }
    }

}
