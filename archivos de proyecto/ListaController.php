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

        return response()->json([
            'data' => $listas
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
            ->with(['inscripciones.postulante', 'inscripciones.area', 'inscripciones.categoria'])
            ->get();

        $formattedListas = $listas->map(function ($lista) {
            return [
                'codigo_lista' => $lista->codigo_lista,
                'nombre_lista' => $lista->nombre_lista,
                'postulantes' => $lista->inscripciones->map(function ($inscripcion) {
                    return [
                        'nombres' => $inscripcion->postulante->nombres,
                        'apellidos' => $inscripcion->postulante->apellidos,
                        'ci' => $inscripcion->postulante->ci,
                        'area' => $inscripcion->area->nombre,
                        'categoria' => $inscripcion->categoria->nombre
                    ];
                })
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
}