<?php

namespace App\Http\Controllers;

use App\Models\Lista;
use App\Models\Responsable;
use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class ListaController extends Controller
{
    /**
     * Crear lista que se asocia al responsable
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre_lista' => 'required|string|max:255',
            'ci' => 'required|string|exists:responsables,ci'
        ], [
            'ci.exists' => 'El CI proporcionado no está registrado', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->first() 
            ], 422);
        }

        $responsable = Responsable::where('ci', $request->ci)->first();

        if (!$responsable) {
            return response()->json([
                'errors' => 'Responsable no encontrado'
            ], 404);
        }

        $lista = $responsable->listas()->create([
            'nombre_lista' => $request->nombre_lista
        ]);

        return response()->json([
            'codigo_lista' => $lista->codigo_lista
        ], 201);
    }
    
    
    /**
     * Mostrar todas las listas
     */
    public function index()
    {
        $listas = Lista::withCount([
            'inscripciones as postulantes_count' => function ($query) {
                $query->select(DB::raw('COUNT(DISTINCT postulante_id)')); 
            }
        ])->get();

        $filteredListas = $listas->map(function ($lista) {
            return [
                'nombre_lista' => $lista->nombre_lista,
                'postulantes_count' => $lista->postulantes_count,
                'fecha_creacion' => $lista->fecha_creacion,
                'estado' => $lista->estado,
                'codigo_lista' => $lista->codigo_lista,
            ];
        });

        return response()->json([
            'data' => $filteredListas
        ], 200);
    }


    /**
     * Mostrar lista por id
     */
    public function show($id)
    {
        $lista = Lista::withCount([
            'inscripciones as postulantes_count' => function ($query) {
                $query->select(DB::raw('COUNT(DISTINCT postulante_id)'));
            }
        ])->find($id);

        if (!$lista) {
            return response()->json(['errors' => 'Lista no encontrada'], 404);
        }

        return response()->json([
            'data' => $lista
        ], 200);
    }


    /**
     * Mostrar listas de un responsable por ci 
     */
    public function getByResponsableCi($ci)
    {
        $responsable = Responsable::where('ci', $ci)->first();

        if (!$responsable) {
            return response()->json(['errors' => 'Responsable no encontrado'], 404);
        }

        $listas = $responsable->listas()
            ->withCount([
                'inscripciones as postulantes_count' => function($query) {
                    $query->select(DB::raw('COUNT(DISTINCT postulante_id)'));
                }
            ])
            ->get();

        $formattedListas = $listas->map(function ($lista) {
            return [
                'nombre_lista' => $lista->nombre_lista,
                'postulantes_count' => $lista->postulantes_count,
                'fecha_creacion' => $lista->fecha_creacion,
                'estado' => $lista->estado,
                'codigo_lista' => $lista->codigo_lista,
            ];
        });

        return response()->json(['data' => $formattedListas], 200);
    }


    /**
     * Mostrar listas por estado
     */
    public function getListasByEstado($estado)
    {
        if (!in_array($estado, ['pendiente', 'pagado'])) {
            return response()->json(['errors' => 'Estado no válido. Use: pendiente o pagado'], 400);
        }

        // Obtener listas filtradas por estado con conteo de postulantes
        $listas = Lista::where('estado', $estado)
                    ->withCount([
                        'inscripciones as postulantes_count' => function($query) {
                            $query->select(DB::raw('COUNT(DISTINCT postulante_id)'));
                        }
                    ])
                    ->get();

        return response()->json([
            'data' => $listas
        ], 200);
    }


    /**
     * Mostrar listas por estado asociado al ci de un responsable
     */
    public function getListasByEstadoYResponsable($ci, $estado)
    {
        if (!in_array($estado, ['pendiente', 'pagado'])) {
            return response()->json(['errors' => 'Estado no válido. Use: pendiente o pagado'], 400);
        }

        $responsable = Responsable::where('ci', $ci)->first();

        if (!$responsable) {
            return response()->json(['errors' => 'Responsable no encontrado'], 404);
        }

        $listas = $responsable->listas()
                    ->where('estado', $estado)
                    ->withCount([
                        'inscripciones as postulantes_count' => function($query) {
                            $query->select(DB::raw('COUNT(DISTINCT postulante_id)'));
                        }
                    ])
                    ->get();

        return response()->json([
            'data' => $listas
        ], 200);
    }


    /**
     * Mostrar listas por codigo
     */
    public function showByCodigo($codigo)
    {
        $lista = Lista::with([
            'responsable',
            'inscripciones.postulante',
            'inscripciones.area',
            'inscripciones.categoria'
        ])->where('codigo_lista', $codigo)->first();

        if (!$lista) {
            return response()->json(['error' => 'Lista no encontrada'], 404);
        }

        $formattedData = [
            'codigo_lista'   => $lista->codigo_lista,
            'nombre_lista'   => $lista->nombre_lista,
            'estado'         => $lista->estado,
            'fecha_creacion' => $lista->fecha_creacion,
            'responsable'    => $lista->responsable->nombre_completo,
            'responsable_id' => $lista->responsable->ci,
            'inscripciones'  => $lista->inscripciones->map(function ($inscripcion) {
                return [
                    'postulante_id' => $inscripcion->postulante->id,
                    'nombres'       => $inscripcion->postulante->nombres,
                    'apellidos'     => $inscripcion->postulante->apellidos,
                    'ci'            => $inscripcion->postulante->ci,
                    'area'          => $inscripcion->area ? $inscripcion->area->nombre : null,
                    'categoria'     => $inscripcion->categoria ? $inscripcion->categoria->nombre : null,
                ];
            })->toArray()
        ];

        return response()->json(['data' => $formattedData], 200);
    }

    /**
     * Actualizar el estado de una lista
     */
    public function updateEstado(Request $request, $codigo)
    {
        $request->validate([
            'estado' => 'required|string|in:pendiente,pagado'
        ]);

        $lista = Lista::where('codigo_lista', $codigo)->first();

        if (!$lista) {
            return response()->json(['error' => 'Lista no encontrada'], 404);
        }

        $lista->estado = $request->estado;
        $lista->save();

        return response()->json([
            'data' => [
                'codigo_lista' => $lista->codigo_lista,
                'estado_actualizado' => $lista->estado
            ]
        ]);
    }
}