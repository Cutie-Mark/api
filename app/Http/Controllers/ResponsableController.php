<?php

namespace App\Http\Controllers;

use App\Models\Responsable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ResponsableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Responsable::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255',
                'apellido' => 'required|string|max:255',
                'ci' => 'required|string|max:10|regex:/^\d{7,8}[A-Za-z]?$/|unique:responsables',
                'email' => 'required|string|email|max:255|unique:responsables',
                'telefono' => 'required|string|max:20',
                'es_profesor' => 'required|boolean',
            ]);

            $responsable = Responsable::create($validatedData);

            return response()->json([
                'message' => 'Responsable creado con éxito',
                'responsable' => $responsable
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Responsable $responsable)
    {
        return response()->json($responsable);
    }

    /**
     * Obtener listas con inscripciones de responsables
     */
    public function listasConInscripciones(Request $request)
    {
        try {
            $request->validate([
                'estado' => 'nullable|in:pendiente,pagado,rechazado'
            ]);

            $estado = $request->query('estado');

            $responsables = Responsable::with(['listas' => function($query) use ($estado) {
                $query->withCount(['inscripciones as inscripciones_count' => function($q) use ($estado) {
                    if ($estado) {
                        $q->where('estado', $estado);
                    }
                }]);
            }])->get();

            return response()->json($responsables);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error interno del servidor',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}