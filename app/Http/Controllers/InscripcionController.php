<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\Lista;
use App\Models\Postulante;
use App\Models\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InscripcionController extends Controller
{
    /**
     * Obtener todas las inscripciones con relaciones.
     */
    public function index()
    {
        $inscripciones = Inscripcion::with([
            'postulante',
            'lista.responsable',
            'categoria',
            'colegio',
            'olimpiada',
            'area'
        ])->get();

        return response()->json($inscripciones);
    }

    /**
     * Crear una o múltiples inscripciones (masivas).
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                // Responsable (datos del tutor)
                'responsable' => 'required|array',
                'responsable.nombre' => 'required|string|max:100',
                'responsable.apellido' => 'required|string|max:100',
                'responsable.ci' => 'required|string|max:10|regex:/^\d{7,8}[A-Za-z]?$/|unique:responsables,ci',
                'responsable.email' => 'required|email|max:100|unique:responsables,email',
                'responsable.telefono' => 'required|string|max:15',
                'responsable.es_profesor' => 'required|boolean',

                // Lista (agrupación de inscripciones)
                /*
                'lista' => 'required|array',
                'lista.nombre_lista' => 'required|string|max:45',
                'lista.codigo_lista' => 'required|string|max:16|unique:listas,codigo_lista',
                'lista.fecha_creacion' => 'nullable|date',
                */
                // Postulantes (array de estudiantes)
                'postulantes' => 'required|array|min:1',
                'postulantes.*.nombre' => 'required|string|max:45',
                'postulantes.*.apellido' => 'required|string|max:60',
                'postulantes.*.fecha_nacimiento' => 'required|date',
                'postulantes.*.provincia_id' => 'required|exists:provincias,id',
                'postulantes.*.correo_postulante' => 'required|email|max:65|unique:postulantes,correo_postulante',
                'postulantes.*.ci' => 'required|string|max:10|regex:/^\d{7,8}[A-Za-z]?$/|unique:postulantes,ci',
                'postulantes.*.curso' => 'required|integer|between:1,12',

                // Datos comunes para todas las inscripciones
                'inscripcion' => 'required|array',
                'inscripcion.email_contacto' => 'required|email|max:55',
                'inscripcion.tipo_contacto_email' => 'required|in:profesor,papa/mama,estudiante',
                'inscripcion.telefono_contacto' => 'required|string|max:13',
                'inscripcion.tipo_contacto_telefono' => 'required|in:profesor,papa/mama,estudiante',
                'inscripcion.categoria_id' => 'required|exists:categorias,id',
                'inscripcion.colegio_id' => 'required|exists:colegios,id',
                'inscripcion.olimpiada_id' => 'required|exists:olimpiadas,id',
                'inscripcion.area_id' => 'required|exists:areas,id',
            ]);

            return DB::transaction(function () use ($validated) {
                // Crear responsable
                $responsable = Responsable::create($validated['responsable']);

                // Crear lista AUTOMATICAMENTE
                $lista = Lista::create([
                    //...$validated['lista'],
                    'responsable_id' => $responsable->id
                ]);

                // Crear postulantes e inscripciones
                foreach ($validated['postulantes'] as $postulanteData) {
                    $postulante = Postulante::create($postulanteData);

                    Inscripcion::create([
                        ...$validated['inscripcion'],
                        'lista_id' => $lista->id,
                        'postulante_id' => $postulante->id
                    ]);
                }

                return response()->json([
                    'message' => 'Inscripción Creada',
                    'lista_id' => $lista->id
                ], 201);
            });

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mostrar detalles de una inscripción.
     */
    public function show(Inscripcion $inscripcion)
    {
        $inscripcion->load([
            'postulante',
            'lista.responsable',
            'categoria',
            'colegio',
            'olimpiada',
            'area'
        ]);

        return response()->json($inscripcion);
    }

    /**
     * Actualizar estado de una inscripción (ej: aprobar/rechazar).
     */
    public function updateEstado(Request $request, Inscripcion $inscripcion)
    {
        try {
            $validated = $request->validate([
                'estado' => 'required|in:pendiente,pagado,rechazado,aprobado'
            ]);

            $inscripcion->update($validated);

            return response()->json([
                'message' => 'Estado actualizado',
                'inscripcion' => $inscripcion
            ]);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    /**
     * Eliminar una inscripción.
     */
    public function destroy(Inscripcion $inscripcion)
    {
        $inscripcion->delete();
        return response()->json(['message' => 'Inscripción eliminada']);
    }

    /**
     * Filtrar inscripciones por estado.
     */
    public function filtrarPorEstado($estado)
    {
        $inscripciones = Inscripcion::with('postulante', 'lista.responsable')
            ->where('estado', $estado)
            ->get();

        return response()->json($inscripciones);
    }

    /**
     * Filtrar inscripciones por responsable.
     */
    public function filtrarPorResponsable(Responsable $responsable)
    {
        $inscripciones = $responsable->listas->flatMap->inscripciones;
        return response()->json($inscripciones);
    }
}