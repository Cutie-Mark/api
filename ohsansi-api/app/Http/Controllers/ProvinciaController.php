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
            $provincia = Provincia::findOrFail($id);
            return response()->json($departamento);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Departamento no encontrado'], 404);
        }
    }
}
