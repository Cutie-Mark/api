<?php

namespace App\Http\Controllers;

use App\Models\Responsable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ResponsableController extends Controller
{
    /**
     * Registra un nuevo responsable
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nombre_completo' => 'required|string|max:255',
                'email' => 'required|email|unique:responsables',
                'telefono' => 'required|string|size:8', 
            ], [
                'email.unique' => 'Ya existe una cuenta registrada con este correo electrónico.',
            ]);

            $responsable = Responsable::create($validatedData);

            // Generar token de autenticación (Sanctum)
            //$token = $responsable->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Responsable registrado exitosamente',
                'uuid' => $responsable->uuid,
                //'token' => $token // Token para autenticar solicitudes futuras
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    /**
     * Obtiene las listas de un responsable con sus inscripciones
     */
    public function listasConInscripciones(Request $request)
    {
        try {
            $request->validate([
                'estado' => 'nullable|in:pendiente,pagado' 
            ]);

            $responsable = Responsable::where('uuid', $request->route('uuid'))->firstOrFail();
            
            $listas = $responsable->listas()
                ->withCount('inscripciones as cantidad_postulantes') 
                ->when($request->estado, function ($query, $estado) {
                    $query->where('estado', $estado);
                })
                ->get(['nombre_lista', 'codigo_lista', 'created_at as fecha_creacion', 'estado']);

            return response()->json($listas);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Lista todos los responsables
     */
    public function index()
    {
        try {
            $responsables = Responsable::select('uuid', 'nombre_completo', 'email', 'telefono', 'created_at')
                ->get();

            return response()->json($responsables);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener la lista de responsables',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Muestra los detalles de un responsable específico
     */
    public function show($uuid)
    {
        try {
            $responsable = Responsable::select('uuid', 'nombre_completo', 'email', 'telefono', 'created_at')
                ->where('uuid', $uuid)
                ->firstOrFail();

            return response()->json($responsable);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Responsable no encontrado'], 404);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }
}