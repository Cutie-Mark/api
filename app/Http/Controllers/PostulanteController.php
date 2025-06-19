<?php

namespace App\Http\Controllers;

use App\Models\Postulante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Crypt;

class PostulanteController extends Controller
{
    /**
     * Crear Postulante (con formato de nombres/apellidos)
     */
    public function crear(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'fecha_nacimiento' => 'required|date|before:today', 
            'provincia_id' => 'required|exists:provincias,id',
            'email' => 'required|email',
            'ci' => 'required|string|max:10',
            'curso' => 'required|integer|between:1,12'
        ], [
            'required' => 'El campo :attribute es obligatorio',
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

        // Verificar si el CI ya existe (necesario ahora que está encriptado)
        $ciExists = false;
        $allPostulantes = Postulante::all();
        foreach ($allPostulantes as $existingPostulante) {
            if ($existingPostulante->ci === $request->ci) {
                $ciExists = true;
                break;
            }
        }

        if ($ciExists) {
            return response()->json([
                'error' => 'Este CI ya está registrado'
            ], 422);
        }

        // Formatear nombres y apellidos
        $data = $validator->validated();
        $data['nombres'] = ucwords(strtolower(trim($data['nombres'])));
        $data['apellidos'] = ucwords(strtolower(trim($data['apellidos'])));

        $postulante = Postulante::create($data);

        return response()->json([
            'mensaje' => 'Postulante registrado exitosamente',
            'data' => $this->formatoDatosPostulante($postulante)
        ], 201);
    }

    /**
     * Obtener todos los postulantes (con provincia y departamento)
     */
    public function listar()
    {
        $postulantes = Postulante::with('provincia.departamento')->get();

        return response()->json([
            'count' => $postulantes->count(),
            'data' => $postulantes->map(function ($postulante) {
                return $this->formatoDatosPostulante($postulante);
            })
        ], 200);
    }

    /**
     * Obtener postulante por ID
     */
    public function mostrar($id)
    {
        try {
            /** @var \App\Models\Postulante $postulante */
            $postulante = Postulante::with('provincia.departamento')->findOrFail($id);
            return response()->json([
                'data' => $this->formatoDatosPostulante($postulante)
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Postulante no encontrado'], 404);
        }
    }

    /**
     * Actualizar postulante (con validación y formato)
     */
    public function actualizar(Request $request, $id)
    {
        try {
            /** @var Postulante $postulante */
            $postulante = Postulante::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'nombres' => 'sometimes|required|string|max:255',
                'apellidos' => 'sometimes|required|string|max:255',
                'fecha_nacimiento' => 'sometimes|required|date|before:today',
                'provincia_id' => 'sometimes|required|exists:provincias,id',
                'email' => 'sometimes|required|email',
                'ci' => 'sometimes|required|string|max:10',
                'curso' => 'sometimes|required|integer|between:1,12'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 422);
            }

            // Si se cambia el CI, verificar que no exista ya (necesario ahora que está encriptado)
            if ($request->has('ci') && $request->ci !== $postulante->ci) {
                $ciExists = false;
                $allPostulantes = Postulante::where('id', '!=', $id)->get();
                foreach ($allPostulantes as $existingPostulante) {
                    if ($existingPostulante->ci === $request->ci) {
                        $ciExists = true;
                        break;
                    }
                }

                if ($ciExists) {
                    return response()->json([
                        'error' => 'Este CI ya está registrado'
                    ], 422);
                }
            }

            // Formatear nombres y apellidos si están presentes
            if ($request->has('nombres')) {
                $request->merge(['nombres' => ucwords(strtolower(trim($request->nombres)))]);
            }
            
            if ($request->has('apellidos')) {
                $request->merge(['apellidos' => ucwords(strtolower(trim($request->apellidos)))]);
            }

            $postulante->update($request->all());

            return response()->json([
                'mensaje' => 'Postulante actualizado correctamente',
                'data' => $this->formatoDatosPostulante($postulante)
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Postulante no encontrado'], 404);
        }
    }

    /**
     * Formatea los datos del postulante para respuestas
     */
    private function formatoDatosPostulante(Postulante $postulante): array
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