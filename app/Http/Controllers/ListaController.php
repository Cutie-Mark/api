<?php

namespace App\Http\Controllers;

use App\Models\Lista;
use App\Models\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ListaController extends Controller
{
    /**
     * Crear lista que se asocia al responsable y a una olimpiada
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre_lista'  => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    $responsable = Responsable::where('ci', $request->ci)->first();
                    if ($responsable) {
                        $nombreLower = strtolower($value);
                        if ($responsable->listas()
                            ->whereRaw('LOWER(nombre_lista) = ?', [$nombreLower])
                            ->exists()
                        ) {
                            $fail('El nombre de la lista ya existe para este responsable.');
                        }
                    }
                },
            ],
            'olimpiada_id'  => 'required|exists:olimpiadas,id',
            'ci'            => 'required|string|exists:responsables,ci',
        ], [
            'required'      => 'El campo :attribute es obligatorio',
            'string'        => 'El campo :attribute debe ser texto',
            'max'           => 'El campo :attribute no debe exceder los :max caracteres',
            'ci.exists'     => 'El CI proporcionado no está registrado',
            'olimpiada_id.exists' => 'La olimpiada especificada no existe'
        ])->setAttributeNames([
            'nombre_lista'  => 'Nombre de lista',
            'ci'            => 'CI',
            'olimpiada_id'  => 'ID de Olimpiada'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 422);
        }

        $responsable = Responsable::where('ci', $request->ci)->first();

        $lista = $responsable->listas()->create([
            'nombre_lista'  => strtolower($request->nombre_lista),
            'olimpiada_id'  => $request->olimpiada_id,
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

        $filtered = $listas->map(fn($lista) => [
            'codigo_lista'      => $lista->codigo_lista,
            'nombre_lista'      => $lista->nombre_lista,
            'olimpiada_id'      => $lista->olimpiada_id,
            'estado'            => $lista->estado,
            'postulantes_count' => $lista->postulantes_count,
            'created_at'        => $lista->created_at->toDateTimeString(),
        ]);

        return response()->json(['data' => $filtered], 200);
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
            return response()->json(['error' => 'Lista no encontrada'], 404);
        }

        return response()->json([
            'data' => [
                'codigo_lista'      => $lista->codigo_lista,
                'nombre_lista'      => $lista->nombre_lista,
                'olimpiada_id'      => $lista->olimpiada_id,
                'estado'            => $lista->estado,
                'postulantes_count' => $lista->postulantes_count,
                'created_at'        => $lista->created_at->toDateTimeString(),
            ]
        ], 200);
    }

    /**
     * Mostrar listas de un responsable por CI
     */
    public function getByResponsableCi($ci)
    {
        $responsable = Responsable::where('ci', $ci)->first();
        if (!$responsable) {
            return response()->json(['error' => 'Responsable no encontrado'], 404);
        }

        $listas = $responsable->listas()
            ->withCount(['inscripciones as postulantes_count'])
            ->get();

        $formatted = $listas->map(fn($lista) => [
            'codigo_lista'      => $lista->codigo_lista,
            'nombre_lista'      => $lista->nombre_lista,
            'olimpiada_id'      => $lista->olimpiada_id,
            'estado'            => $lista->estado,
            'postulantes_count' => $lista->postulantes_count,
            'created_at'        => $lista->created_at->toDateTimeString(),
        ]);

        return response()->json(['data' => $formatted], 200);
    }

    /**
     * Mostrar listas por estado
     */
    public function getListasByEstado($estado)
    {
        if (!in_array($estado, ['Preinscrito', 'Pago Pendiente', 'Inscripcion Completa'])) {
            return response()->json(['error' => 'Estado no válido. Use: pendiente o pagado'], 400);
        }

        $listas = Lista::where('estado', $estado)
            ->withCount(['inscripciones as postulantes_count'])
            ->get();

        $formatted = $listas->map(fn($lista) => [
            'codigo_lista'      => $lista->codigo_lista,
            'nombre_lista'      => $lista->nombre_lista,
            'olimpiada_id'      => $lista->olimpiada_id,
            'estado'            => $lista->estado,
            'postulantes_count' => $lista->postulantes_count,
            'created_at'        => $lista->created_at->toDateTimeString(),
        ]);

        return response()->json(['data' => $formatted], 200);
    }

    /**
     * Mostrar listas por estado y responsable
     */
    public function getListasByEstadoYResponsable($ci, $estado)
    {
        if (!in_array($estado, ['Preinscrito', 'Pago Pendiente', 'Inscripcion Completa'])) {
            return response()->json(['error' => 'Estado no válido. Use: pendiente o pagado'], 400);
        }

        $responsable = Responsable::where('ci', $ci)->first();
        if (!$responsable) {
            return response()->json(['error' => 'Responsable no encontrado'], 404);
        }

        $listas = $responsable->listas()
            ->where('estado', $estado)
            ->withCount(['inscripciones as postulantes_count'])
            ->get();

        $formatted = $listas->map(fn($lista) => [
            'codigo_lista'      => $lista->codigo_lista,
            'nombre_lista'      => $lista->nombre_lista,
            'olimpiada_id'      => $lista->olimpiada_id,
            'estado'            => $lista->estado,
            'postulantes_count' => $lista->postulantes_count,
            'created_at'        => $lista->created_at->toDateTimeString(),
        ]);

        return response()->json(['data' => $formatted], 200);
    }

    /**
     * Mostrar lista por código
     */
    public function showByCodigo($codigo)
    {
        $lista = Lista::with([
                'inscripciones.postulante',
                'inscripciones.nivelCompetencia.area',
                'inscripciones.nivelCompetencia.categoria',
            ])
            ->where('codigo_lista', $codigo)
            ->first();

        if (!$lista) {
            return response()->json(['error' => 'Lista no encontrada'], 404);
        }

        $inscripciones = $lista->inscripciones->map(function ($i) {
            $nc = $i->nivelCompetencia;
            return [
                'postulante_id' => $i->postulante->id,
                'nombres'       => $i->postulante->nombres,
                'apellidos'     => $i->postulante->apellidos,
                'ci'            => $i->postulante->ci,
                'area'          => $nc && $nc->area ? $nc->area->nombre : null,
                'categoria'     => $nc && $nc->categoria ? $nc->categoria->nombre : null,
            ];
        });

        return response()->json([
            'data' => $inscripciones
        ], 200);
    }


    /**
     * Actualizar el estado de una lista
     */
    public function updateEstado(Request $request, $codigo)
    {
        $request->validate([
            'estado' => 'required|string|in:Preinscrito,Pago Pendiente,Inscripcion Completa',
        ]);

        $lista = Lista::where('codigo_lista', $codigo)->first();
        if (!$lista) {
            return response()->json(['error' => 'Lista no encontrada'], 404);
        }

        $lista->estado = $request->estado;
        $lista->save();

        return response()->json([
            'data' => [
                'codigo_lista'       => $lista->codigo_lista,
                'estado_actualizado' => $lista->estado
            ]
        ], 200);
    }

    /**
     * Mostrar listas por olimpiada
     */
    public function getByOlimpiada($olimpiadaId)
    {
        $listas = Lista::where('olimpiada_id', $olimpiadaId)
            ->withCount(['inscripciones as postulantes_count'])
            ->get()
            ->map(fn($lista) => [
                'codigo_lista'      => $lista->codigo_lista,
                'nombre_lista'      => $lista->nombre_lista,
                'olimpiada_id'      => $lista->olimpiada_id,
                'estado'            => $lista->estado,
                'postulantes_count' => $lista->postulantes_count,
                'created_at'        => $lista->created_at->toDateTimeString(),
            ]);

        return response()->json(['data' => $listas], 200);
    }
}