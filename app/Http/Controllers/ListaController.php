<?php

namespace App\Http\Controllers;

use App\Models\Lista;
use App\Models\Responsable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ListaController extends Controller
{
    /**
     * Crear lista asociada al responsable autenticado.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre_lista' => 'required|string|max:45'
            ]);

            $responsable = $request->user();

            $lista = Lista::create([
                'nombre_lista' => $validated['nombre_lista'],
                'id_responsable' => $responsable->uuid // Relación por UUID
            ]);

            return response()->json([
                'message' => 'Lista creada exitosamente',
                'codigo_lista' => $lista->codigo_lista
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    /**
     * Buscar lista por su UUID (codigo_lista).
     */
    public function porCodigo($codigo)
    {
        try {
            $lista = Lista::with(['responsable', 'inscripciones.postulante'])
                ->where('codigo_lista', $codigo)
                ->firstOrFail();

            return response()->json($lista);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Lista no encontrada'], 404);
        }
    }

    /**
     * Listas de un responsable por su UUID.
     */
    public function porResponsable($uuid)
    {
        try {
            $responsable = Responsable::where('uuid', $uuid)
                ->firstOrFail();

            $listas = $responsable->listas()
                ->withCount('inscripciones as cantidad_postulantes')
                ->get(['nombre_lista', 'codigo_lista', 'estado', 'fecha_creacion']);

            return response()->json($listas);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Responsable no encontrado'], 404);
        }
    }

    /**
     * Actualizar estado de una lista.
     */
    public function updateEstado(Request $request, $codigoLista) {
        try {
            $validated = $request->validate([
                'estado' => 'required|in:pendiente,pagado'
            ]);
    
            $lista = Lista::where('codigo_lista', $codigoLista)->firstOrFail();
            $lista->update(['estado' => $validated['estado']]);
    
            return response()->json([
                'message' => 'Estado de la lista actualizado',
                'estado' => $lista->estado
            ]);
    
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Lista no encontrada'], 404);
        }
    }

    /**
     * Listar todas las listas (para administradores).
     */
    public function index()
    {
        try {
            $listas = Lista::withCount('inscripciones as cantidad_postulantes')
                ->get(['nombre_lista', 'codigo_lista', 'estado', 'fecha_creacion']);

            return response()->json($listas);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener listas',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // Listar listas por estado
    public function listarPorEstado($estado) {
        try {
            $listas = Lista::where('estado', $estado)
                ->withCount('inscripciones as cantidad_postulantes')
                ->get(['nombre_lista', 'codigo_lista', 'fecha_creacion', 'estado']);

            return response()->json($listas);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mostrar lista por ID (no por UUID).
     */
    public function show($id)
    {
        try {
            $lista = Lista::with(['responsable', 'inscripciones.postulante'])
                ->findOrFail($id);

            return response()->json($lista);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Lista no encontrada'], 404);
        }
    }
}