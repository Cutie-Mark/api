<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoriaOlimpiada;
use App\Models\Categoria;
use App\Services\OlimpiadaService;

class CategoriaOlimpiadaController extends Controller
{
    protected $olimpiadaService;

    public function __construct(OlimpiadaService $olimpiadaService)
    {
        $this->olimpiadaService = $olimpiadaService;
    }
    
    public function store(Request $request)
    {
        try {
            if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden registrar areas nuevas, Hay un evento en curso, espere a que finalice.'], 400);
            }

            $request->validate([
                'categoria_id' => 'required|exists:categorias,id',
                'olimpiada_id' => 'required|exists:olimpiadas,id',
            ]);

            $registro = CategoriaOlimpiada::create([
                'categoria_id' => $request->categoria_id,
                'olimpiada_id' => $request->olimpiada_id,
            ]);

            return response()->json($registro, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar categoría en la olimpiada.', 'error' => $e->getMessage()], 500);
        }
    }

    // Eliminar una relación categoría-olimpiada
    public function destroy($id)
    {
        try {
            if ($this->olimpiadaService->hayOlimpiadaEnCurso()) {
                return response()->json(['error' => 'No se pueden registrar areas nuevas, Hay un evento en curso, espere a que finalice.'], 400);
            }
            
            $registro = CategoriaOlimpiada::findOrFail($id);
            $registro->delete();

            return response()->json(['message' => 'Registro eliminado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar el registro.', 'error' => $e->getMessage()], 500);
        }
    }

    // Obtener todas las categorías asociadas a una olimpiada específica
    public function getCategoriasByOlimpiada($olimpiada_id)
    {
        try {
            $categorias = Categoria::whereHas('olimpiadas', function ($query) use ($olimpiada_id) {
                $query->where('olimpiadas.id', $olimpiada_id);
            })->get();

            return response()->json($categorias, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener las categorías.', 'error' => $e->getMessage()], 500);
        }
    }
}
