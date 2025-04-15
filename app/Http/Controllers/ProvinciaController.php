<?php

namespace App\Http\Controllers;


use App\Models\Provincia;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProvinciaController extends Controller
{
    // 1. Crear una nueva provincia
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:35',
            'departamento_id' => 'required|exists:departamentos,id'
        ]);

        $existeProvincia = Provincia::where('nombre', $request->nombre)
            ->where('departamento_id', $request->departamento_id)
            ->exists();

        if ($existeProvincia) {
            return response()->json([
                'error' => 'El nombre de la provincia ya existe en este departamento.'
            ], 422);
        }

        $provincia = Provincia::create($request->all());
        return response()->json($provincia, 201);
    }


    // 2. Obtener todos las provincias
    public function index()
    {
        return response()->json(Provincia::all());
    }

    
    // 3. Obtener una provincia por su ID
    public function show($id)
    {
        try {
            $provincia = Provincia::with('departamento')->findOrFail($id);//carga la relacion
            return response()->json($provincia);//correcion:departamento por provincia
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'provincia no encontrada'], 404);
        }
    }
}
