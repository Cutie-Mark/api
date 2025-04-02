<?php

namespace App\Http\Controllers;

use App\Models\Postulante;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PostulanteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Postulante::all());
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
                'fecha_nacimiento' => 'required|date',
                'provincia_id' => 'required|exists:provincias,id',
                'correo_postulante' => 'required|string|email|max:255|unique:postulantes',
                'ci' => 'required|string|size:10|regex:/^\d{7,8}[A-Za-z]?$/|unique:postulantes',
                'curso' => 'required|integer|between:1,12',
            ]);

            $postulante = Postulante::create($validatedData);

            return response()->json(['message' => 'Postulante creado con éxito', 'postulante' => $postulante], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $postulante = Postulante::find($id);
        if (!$postulante) {
            return response()->json(['message' => 'Postulante no encontrado'], 404);
        }
        return response()->json($postulante);
    }


}
