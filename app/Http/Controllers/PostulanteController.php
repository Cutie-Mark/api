<?php

namespace App\Http\Controllers;

use App\Models\Postulante;
use Illuminate\Http\Request;
use App\Models\Inscripcion;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PostulanteController extends Controller
{
    /**
     * Crear Postulante
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'fecha_nacimiento' => 'required|date',
            'provincia_id' => 'required|exists:provincias,id', 
            'email' => 'required|email|unique:postulantes',
            'ci' => 'required|string|max:10|unique:postulantes',
            'curso' => 'required|integer|between:1,12' 
        ], [
            // Mensajes de datos obligatorios 
            'required' => 'El campo :attribute es obligatorio',
            //Mensajes para unique
            'email.unique' => 'Este email ya está registrado',
            'ci.unique' => 'Este CI ya está registrado',
            'curso.between' => 'El curso debe estar entre 1 y 12'
        ])->setAttributeNames([
            'nombres' => 'Nombres',
            'apelldios' => 'Apellidos',
            'ci' => 'CI',
            'fecha_nacimiento' => 'Fecha de Nacimeinto',
            'provincia_id' => 'Provincia',
            'email' => 'Email',
            'curso' => 'Curso'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 422);
        }

        $postulante = Postulante::create($validator->validated());

        return response()->json([
            'message' => 'Postulante registrado exitosamente',
            'data' => $postulante
        ], 201);
    }


    /**
     * Obtener todos los postulantes
     */
    public function index()
{
    $postulantes = Postulante::with('provincia.departamento')->get();

    $formattedPostulantes = $postulantes->map(function ($postulante) {
        return [
            'nombres' => $postulante->nombres,
            'apellidos' => $postulante->apellidos,
            'fecha_nacimiento' => $postulante->fecha_nacimiento,
            'departamento' => $postulante->provincia->departamento->nombre, 
            'provincia' => $postulante->provincia->nombre, 
            'email' => $postulante->email,
            'ci' => $postulante->ci,
            'curso' => $postulante->curso
        ];
    });

    return response()->json([
        'data' => $formattedPostulantes
    ], 200);
}


    /**
     * Obtener postulante por id
     */
    public function show($id)
    {
        $postulante = Postulante::with('provincia.departamento')->find($id);

        if (!$postulante) {
            return response()->json(['error' => 'Postulante no encontrado'], 404);
        }

        $formattedPostulante = [
            'nombres' => $postulante->nombres,
            'apellidos' => $postulante->apellidos,
            'fecha_nacimiento' => $postulante->fecha_nacimiento,
            'departamento' => $postulante->provincia->departamento->nombre,
            'provincia' => $postulante->provincia->nombre,
            'email' => $postulante->email,
            'ci' => $postulante->ci,
            'curso' => $postulante->curso
        ];

        return response()->json([
            'data' => $formattedPostulante
        ], 200);
    }
}