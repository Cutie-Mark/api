<?php

namespace App\Http\Controllers;

use App\Models\Lista;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ListaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Lista::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nombre_lista' => 'required|string|max:255',
            ]);

            $lista = Lista::create($validatedData);

            return response()->json(['message' => 'Lista creada con éxito', 'lista' => $lista], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $lista = Lista::find($id);
        if (!$lista) {
            return response()->json(['message' => 'Lista no encontrada'], 404);
        }
        return response()->json($lista);
    }

    public function showByCodigo(string $codigo_lista)
    {
        $lista = Lista::where('codigo_lista', $codigo_lista)->first();
        
        if (!$lista) {
            return response()->json(['message' => 'Lista no encontrada'], 404);
        }
        
        return response()->json($lista);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $lista = Lista::findOrFail($id);

        $request->validate([
            'nombre_lista' => 'sometimes|string|max:255',
        ]);

        $lista->update($request->only('nombre_lista'));

        return response()->json($lista);
    }
}
