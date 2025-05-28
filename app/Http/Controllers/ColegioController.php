<?php

namespace App\Http\Controllers;

use App\Models\Colegio;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;

class ColegioController extends Controller
{
    /**
     * Listar todos los colegios (Index)
     */
    public function index()
    {
        return response()->json(Colegio::all());
    }

    /**
     * Mostrar un colegio por ID (Show)
     */
    public function show($id)
    {
        try {
            $colegio = Colegio::findOrFail($id);
            return response()->json($colegio);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Colegio no encontrado'], 404);
        }
    }

}