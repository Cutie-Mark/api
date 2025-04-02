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
                'email' => 'required|string|email|max:255|unique:responsables',
                'telefono' => 'required|string|max:20',
            ]);

            $responsable = Responsable::create($validatedData);

            return response()->json(['message' => 'Responsable creado con éxito', 'responsable' => $responsable], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $responsable = Responsable::find($id);
        if (!$responsable) {
            return response()->json(['message' => 'Responsable no encontrado'], 404);
        }
        return response()->json($responsable);
    }

}
