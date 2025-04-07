<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\Postulante;
use App\Models\Categoria;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InscripcionController extends Controller
{
    /**
     * Crear una inscripción (con postulante) asociada a una lista existente.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                // Datos del postulante
                'postulante' => 'required|array',
                'postulante.nombres' => 'required|string|max:255',
                'postulante.apellidos' => 'required|string|max:255',
                'postulante.fecha_nacimiento' => 'required|date|before:-10 years',
                'postulante.provincia_id' => 'required|exists:provincias,id',
                'postulante.email' => 'required|email|unique:postulantes,email',
                'postulante.ci' => 'required|string|max:15|unique:postulantes,ci',
                'postulante.curso' => 'required|integer|between:1,12',

                // Datos de la inscripción
                'inscripcion' => 'required|array',
                'inscripcion.email' => 'required|email|max:55',
                'inscripcion.tipo_contacto_email' => 'required|in:padre/madre,profesor,estudiante',
                'inscripcion.telefono' => 'required|string|max:15',
                'inscripcion.tipo_contacto_telefono' => 'required|in:padre/madre,profesor,estudiante',
                'inscripcion.area_id' => 'required|exists:areas,id',
                'inscripcion.categoria_id' => 'required|exists:categorias,id',
                'inscripcion.colegio_id' => 'required|exists:colegios,id',
                'inscripcion.olimpiada_id' => 'required|exists:olimpiadas,id',
                'inscripcion.lista_id' => 'required|exists:listas,codigo_lista', // UUID de la lista
            ]);

            return DB::transaction(function () use ($validated, $request) {
                // Validar categoría permitida para el curso
                $categoria = Categoria::findOrFail($validated['inscripcion']['categoria_id']);

                if (
                    $validated['postulante']['curso'] < $categoria->minimo_grado ||
                    $validated['postulante']['curso'] > $categoria->maximo_grado
                ) {
                    throw ValidationException::withMessages([
                        'categoria_id' => 'La categoría no es válida para el curso del postulante.'
                    ]);
                }

                // Validar máximo 2 áreas por postulante
                //
                $postulanteExistente = Postulante::where('ci', $validated['postulante']['ci'])->first();
                if ($postulanteExistente && $postulanteExistente->inscripciones()->where('area_id', $validated['inscripcion']['area_id'])
                        ->count()>=2){
                        throw ValidationException::withMessages([
                            'area_id' => 'El postulante ya está inscrito en 2 áreas.'
                        ]);
                    }

                // Crear postulante e inscripción
                $postulante = Postulante::create($validated['postulante']);
                $inscripcion = $postulante->inscripciones()->create([
                    ...$validated['inscripcion'],
                    'lista_id' => $validated['inscripcion']['lista_id'] // Usar UUID directamente
                ]);

                return response()->json([
                    'message' => 'Inscripción registrada',
                    'codigo_lista' => $validated['inscripcion']['lista_id'],
                    'inscripcion_id' => $inscripcion->id
                ], 201);
            });

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Lista no encontrada'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar estado de una inscripción .
     */
    public function updateEstado(Request $request, Inscripcion $inscripcion) {
        try {
            $validated = $request->validate([
                'estado' => 'required|in:pendiente,pagado'
            ]);
    
            $inscripcion->update($validated);
    
            return response()->json([
                'message' => 'Estado de la inscripción actualizado',
                'estado' => $inscripcion->estado
            ]);
    
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }
    
    /**
     * Listar todas las inscripciones con relaciones (uso administrativo).
     */
    public function index()
    {
        try {
            $inscripciones = Inscripcion::with([
                'postulante.provincia',
                'lista.responsable',
                'area',
                'categoria',
                'colegio',
                'olimpiada'
            ])->get();

            return response()->json($inscripciones);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener inscripciones',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de una inscripción específica.
     */
    public function show($id)
    {
        try {
            $inscripcion = Inscripcion::with([
                'postulante.provincia',
                'lista.responsable',
                'area',
                'categoria',
                'colegio',
                'olimpiada'
            ])->findOrFail($id);

            return response()->json($inscripcion);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Inscripción no encontrada'], 404);
        }
    }

    /**
     * Mostrar inscripciones filtradas por un área específica.
     */
    public function getByArea($areaId)
    {
        try {
            // Verificar si el área existe
            $area = Area::findOrFail($areaId);

            // Obtener inscripciones del área con relaciones
            $inscripciones = Inscripcion::with([
                'postulante.provincia',
                'lista.responsable',
                'area',
                'categoria',
                'colegio',
                'olimpiada'
            ])->where('area_id', $areaId)->get();

            return response()->json($inscripciones);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Área no encontrada'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener inscripciones por área',
                'details' => $e->getMessage()
            ], 500);
        }
    }

}