<?php

namespace App\Http\Controllers;

use App\Models\Lista;
use App\Models\Responsable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ListaController extends Controller
{
    /**
     * Obtener todas las listas con relaciones.
     */
    public function index()
    {
        $listas = Lista::with(['responsable', 'inscripciones.postulante'])->get();
        return response()->json($listas);
    }

    /**
     * Crear una lista (código generado automáticamente).
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre_lista' => 'required|string|max:45',
                'responsable_id' => 'required|exists:responsables,id'
            ]);

            $lista = Lista::create($validated);

            return response()->json([
                'message' => 'Lista creada',
                'lista' => $lista
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    /**
     * Mostrar una lista específica con detalles completos.
     */
    public function show(Lista $lista)
    {
        $lista->load(['responsable', 'inscripciones.postulante']);
        return response()->json($lista);
    }

    /**
     * Actualizar datos de una lista.
     */
    public function update(Request $request, Lista $lista)
    {
        try {
            $validated = $request->validate([
                'nombre_lista' => 'sometimes|string|max:45'
            ]);

            $lista->update($validated);

            return response()->json([
                'message' => 'Lista actualizada',
                'lista' => $lista
            ]);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    /**
     * Eliminar una lista y sus inscripciones (en cascada).
     */
    public function destroy(Lista $lista)
    {
        $lista->delete();
        return response()->json(['message' => 'Lista eliminada']);
    }

    /**
     * Obtener listas por responsable.
     */
    public function porResponsable(Responsable $responsable)
    {
        $listas = $responsable->listas()->with('inscripciones')->get();
        return response()->json($listas);
    }

    /**
     * Buscar lista por código.
     */
    public function porCodigo($codigo)
    {
        $lista = Lista::where('codigo_lista', $codigo)
            ->with(['responsable', 'inscripciones.postulante'])
            ->firstOrFail();

        return response()->json($lista);
    }
}