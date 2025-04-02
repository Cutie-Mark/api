<?php

namespace App\Http\Controllers;

use App\Models\Colegio;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ColegioController extends Controller
{
    // 1. Obtener todos los colegios
    public function index()
    {
        return response()->json(Colegio::all());
    }

    // 2. Registrar un nuevo colegio
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        $colegio = Colegio::create($request->all());

        return response()->json(['message' => 'Colegio creado con éxito', 'colegio' => $colegio], 201);
    }

    // 4. Eliminar un colegio por ID
    public function destroy($id)
    {
        try {
            $colegio = Colegio::findOrFail($id);
            $colegio->delete();
            return response()->json(['message' => 'Colegio eliminado']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Colegio no encontrado'], 404);
        }
    }
}
