<?php

namespace App\Http\Controllers;


use App\Models\Provincia;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProvinciaController extends Controller
{
    // 1. Obtener todos las provincias
    public function index()
    {
        return response()->json(Provincia::all());
    }

    // 2. Obtener una provincia por su ID
    public function show($id)
    {
        try {
            $provincia = Provincia::with('departamento')->findOrFail($id);//carga la relacion
            return response()->json($provincia);//correcion:departamento por provincia
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'provincia no encontrado'], 404);
        }
    }
}
