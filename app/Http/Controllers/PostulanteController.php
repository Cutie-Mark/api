<?php

namespace App\Http\Controllers;

use App\Models\Postulante;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PostulanteController extends Controller
{
    /**
     * Listar todos los postulantes (uso administrativo).
     */
    public function index()
    {
        try {
            $postulantes = Postulante::with('provincia')->get();
            return response()->json($postulantes);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener postulantes',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo postulante.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nombres' => 'required|string|max:255',
                'apellidos' => 'required|string|max:255',
                'fecha_nacimiento' => 'required|date|before:-10 years', 
                'provincia_id' => 'required|exists:provincias,id',
                'email' => 'required|email|unique:postulantes', 
                'ci' => 'required|string|max:10|unique:postulantes', 
                'curso' => 'required|integer|between:1,12',
            ], [
                'email.unique' => 'El correo electrónico ya está registrado.',
                'ci.unique' => 'El número de identificación ya existe.',
                'fecha_nacimiento.before' => 'El postulante debe tener al menos 10 años.'
            ]);

            $postulante = Postulante::create($validatedData);

            return response()->json([
                'message' => 'Postulante registrado exitosamente',
                'postulante' => $postulante
            ], 201);

        } catch (ValidationException $e) {
            $validator = $e->validator;
            $failedRules = $validator->failed();
    
            // Manejar errores de unicidad
            $errors = [];
            if (isset($failedRules['email']['Unique'])) {
                $errors = ['errors' => 'Ya existe una cuenta registrada con el correo'];
            } elseif (isset($failedRules['ci']['Unique'])) {
                $errors = ['errors' => 'El número de identificación ya está registrado'];
            }
    
            if (!empty($errors)) {
                return response()->json($errors, 422);
            }
    
            return response()->json(['errors' => $e->errors()], 422);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error interno del servidor',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de un postulante.
     */
    public function show($id)
    {
        try {
            $postulante = Postulante::with(['provincia', 'inscripciones'])
                ->findOrFail($id);

            return response()->json($postulante);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Postulante no encontrado'], 404);
        }
    }
}