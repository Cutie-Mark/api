<?php

namespace App\Http\Controllers;

use App\Models\Postulante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PostulanteController extends Controller
{
    /**
     * Crear Postulante (con formato de nombres/apellidos)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'fecha_nacimiento' => 'required|date|before:today', 
            'provincia_id' => 'required|exists:provincias,id',
            'email' => 'required|email|unique:postulantes',
            'ci' => 'required|string|max:10|unique:postulantes',
            'curso' => 'required|integer|between:1,12'
        ], [
            'required' => 'El campo :attribute es obligatorio',
            'email.unique' => 'Este email ya está registrado',
            'ci.unique' => 'Este CI ya está registrado',
            'curso.between' => 'El curso debe estar entre 1 y 12',
            'fecha_nacimiento.before' => 'La fecha de nacimiento no puede ser futura' 
        ])->setAttributeNames([
            'nombres' => 'Nombres',
            'apellidos' => 'Apellidos',
            'ci' => 'CI',
            'fecha_nacimiento' => 'Fecha de Nacimiento',
            'provincia_id' => 'Provincia',
            'email' => 'Email',
            'curso' => 'Curso'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 422);
        }

        // Formatear nombres y apellidos
        $data = $validator->validated();
        $data['nombres'] = ucwords(strtolower(trim($data['nombres'])));
        $data['apellidos'] = ucwords(strtolower(trim($data['apellidos'])));

        $postulante = Postulante::create($data);

        return response()->json([
            'mensaje' => 'Postulante registrado exitosamente',
            'data' => $this->formatPostulanteData($postulante)
        ], 201);
    }

    /**
     * Obtener todos los postulantes (con provincia y departamento)
     */
    public function index()
    {
        $postulantes = Postulante::with('provincia.departamento')->get();

        return response()->json([
            'count' => $postulantes->count(),
            'data' => $postulantes->map(function ($postulante) {
                return $this->formatPostulanteData($postulante);
            })
        ], 200);
    }

    /**
     * Obtener postulante por ID
     */
    public function show($id)
    {
        try {
            /** @var \App\Models\Postulante $postulante */
            $postulante = Postulante::with('provincia.departamento')->findOrFail($id);
            return response()->json([
                'data' => $this->formatPostulanteData($postulante)
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Postulante no encontrado'], 404);
        }
    }

    /**
     * Actualizar postulante (con validación y formato)
     */
    public function update(Request $request, $id)
    {
        try {
            /** @var Postulante $postulante */
            $postulante = Postulante::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'nombres' => 'required|string|max:255',
                'apellidos' => 'required|string|max:255',
                'fecha_nacimiento' => 'required|date|before:today', 
                'provincia_id' => 'required|exists:provincias,id',
                'email' => 'required|email|unique:postulantes,email,' . $postulante->id,
                'ci' => 'required|string|max:10|unique:postulantes,ci,' . $postulante->id,
                'curso' => 'required|integer|between:1,12'
            ], [
                'required' => 'El campo :attribute es obligatorio',
                'email.unique' => 'Este email ya está registrado',
                'ci.unique' => 'Este CI ya está registrado',
                'curso.between' => 'El curso debe estar entre 1 y 12',
                'fecha_nacimiento.before' => 'La fecha de nacimiento no puede ser futura'
            ])->setAttributeNames([
                'nombres' => 'Nombres',
                'apellidos' => 'Apellidos',
                'ci' => 'CI',
                'fecha_nacimiento' => 'Fecha de Nacimiento',
                'provincia_id' => 'Provincia',
                'email' => 'Email',
                'curso' => 'Curso'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()->first()
                ], 422);
            }

            // Formatear nombres y apellidos
            $data = $validator->validated();
            $data['nombres'] = ucwords(strtolower(trim($data['nombres'])));
            $data['apellidos'] = ucwords(strtolower(trim($data['apellidos'])));

            $postulante->update($data);

            return response()->json([
                'mensaje' => 'Postulante actualizado exitosamente',
                'data' => $this->formatPostulanteData($postulante)
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Postulante no encontrado'], 404);
        }
    }

    /**
     * Formatea los datos del postulante para respuestas
     */
    private function formatPostulanteData(Postulante $postulante): array
    {
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
    }
}