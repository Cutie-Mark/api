<?php

namespace App\Http\Controllers;

use App\Services\OlimpiadaService;
use App\Services\CategoriaService;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class CategoriaController extends Controller
{

    protected $olimpiadaService;
    protected $categoriaService;

    public function __construct(OlimpiadaService $olimpiadaService, CategoriaService $categoriaService)
    {
        $this->olimpiadaService = $olimpiadaService;
        $this->categoriaService = $categoriaService;
    }

    // Obtener todas las categorías
    public function listar()
    {
        return response()->json(Categoria::all());
    }

    public function buscar(Request $request)
    {
        $nombre = $request->query('nombre');

        $categorias = $nombre
            ? Categoria::where('nombre', 'ILIKE', "%$nombre%")->get()
            : Categoria::all();

        return response()->json($categorias);
    }

    // Registrar una nueva categoría
    public function guardar(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nombre' => 'required|string|unique:categorias,nombre',
                'minimo_grado' => 'required|integer|min:1|max:12',
                'maximo_grado' => 'required|integer|min:1|max:12|gte:minimo_grado',
            ], [
                'nombre.required' => 'El nombre es obligatorio.',
                'minimo_grado.required' => 'Debe indicar el grado mínimo.',
                'maximo_grado.required' => 'Debe indicar el grado máximo.',
            ]);

            $categoria = $this->categoriaService->crear($validatedData);

            if ($categoria === 'duplicado') {
                return response()->json(['error' => 'Esta categoría ya existe. Intente con otra.'], 422);
            }

            return response()->json([
                'message' => 'La categoría se registró correctamente.',
                'categoria' => $categoria
            ], 201);

        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo registrar la categoría. Intente nuevamente.'], 500);
        }
    }

    // Modificar una categoría
    public function actualizar(Request $request, $id)
    {
        try {

            $validatedData = $request->validate([
                'minimo_grado' => 'sometimes|integer|min:1|max:12|lte:maximo_grado',
                'maximo_grado' => 'sometimes|integer|min:1|max:12|gte:minimo_grado',
            ]);

            $categoria = $this->categoriaService->actualizar($id, $validatedData);

            return response()->json(['message' => 'Categoría editada correctamente.', 
                                     'categoria' => $categoria]);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Categoría no encontrada.'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'La edición no se guardó, inténtelo de nuevo.'], 500);
        }
    }

    // Eliminar una categoría
    public function eliminar($id)
    {
        try {
            $categoria = $this->categoriaService->eliminar($id);

            if ($categoria === 'usada') {
                return response()->json(['error' => 'No se puede eliminar la categoría porque ya esta en uso en una olimpiada.'], 400);
            }

            return response()->json(['message' => 'La categoría se eliminó correctamente.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Categoría no encontrada.'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Hubo un error al eliminar la categoría, intente de nuevo.'], 500);
        }
    }

    // Desactivar categorias
    public function desactivar($id)
    {
        try {
            $this->categoriaService->cambiarVigencia($id, false);
            return response()->json(['message' => 'Se dio de baja la categoría.']);
        } catch (ModelNotFoundException) {
            return response()->json(['error' => 'Categoría no encontrada.'], 404);
        } catch (\Throwable) {
            return response()->json(['error' => 'Error al desactivar la categoría.'], 500);
        }

    }

    public function activar($id)
    {
        try {
            $this->categoriaService->cambiarVigencia($id, true);
            return response()->json(['message' => 'Se habilitó la categoría.']);
        } catch (ModelNotFoundException) {
            return response()->json(['error' => 'Categoría no encontrada.'], 404);
        } catch (\Throwable) {
            return response()->json(['error' => 'Error al activar la categoría.'], 500);
        }
    }
}
