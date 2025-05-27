<?php

namespace App\Http\Controllers;

use App\Services\OlimpiadaService;
use App\Services\TextoService;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;


class CategoriaController extends Controller
{

    protected $olimpiadaService;
    protected $textoService;

    public function __construct(OlimpiadaService $olimpiadaService, TextoService $textoService)
    {
        $this->olimpiadaService = $olimpiadaService;
        $this->textoService = $textoService;
    }

    // Obtener todas las categorías
    public function index()
    {
        return response()->json(Categoria::all());
    }

    public function find(Request $request)
    {
        $nombre = $request->query('nombre');

        $categorias = $nombre
            ? Categoria::where('nombre', 'ILIKE', "%$nombre%")->get()
            : Categoria::all();

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
                'minimo_grado.required' => 'Debe indicar el grado mínimo.',
                'maximo_grado.required' => 'Debe indicar el grado máximo.',
            ]);

            $nombreIngresado = $validatedData['nombre'];
            $upper = mb_strtoupper($nombreIngresado, 'UTF-8');
            $nombreNormalizado = $this->textoService->normalizar($upper);

            $existe = Categoria::get()->contains(fn($categoria) => 
                $this->textoService->normalizar(mb_strtoupper($categoria->nombre, 'UTF-8')) === $nombreNormalizado
            );

            if ($existe) {
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
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);
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

            return response()->json(['message' => 'Categoría editada correctamente.', 
                                     'categoria' => $categoria]);

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
        return $this->cambiarVigencia($id, false);

    }

    public function activate($id)
    {
        return $this->cambiarVigencia($id, true);

    }

    public function cambiarVigencia($id, bool $estado)
    {
        try {
            $categoria = Categoria::findOrFail($id);
            $categoria->vigente = $estado;
            $categoria->save();

            return response()->json([
                'message' => $estado ? 'Se activó la categoría.' : 'Se dio de baja la categoría.'
            ]);

        } catch (ModelNotFoundException) {
            return response()->json(['error' => 'Categoría no encontrada.'], 404);
        } catch (\Throwable) {
            return response()->json(['error' => 'Error al cambiar la vigencia.'], 500);
        }
    }

}
