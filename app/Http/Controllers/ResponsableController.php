<?php

namespace App\Http\Controllers;

use App\Models\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ResponsableController extends Controller
{
    /**
     * Registra un nuevo responsable
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre_completo' => 'required|string|max:255',
            'ci' => 'required|string|max:15|unique:responsables',
            'email' => 'required|email|unique:responsables',
            'telefono' => 'required|string|max:8'
        ], [
            // Mensajes de datos obligatorios
            'required' => 'El campo :attribute es obligatorio',
            //mensajes para datos unicos
            'email.unique' => 'Ya existe una cuenta registrada con el correo',
            'ci.unique' => 'Ya existe una cuenta registrada con el ci'
        ])->setAttributeNames([
            'nombre_completo' => 'Nombre Completo',
            'ci' => 'CI',
            'email' => 'Email',
            'telefono' => 'Telefono'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first() 
            ], 422);
        }

        $responsable = Responsable::create($validator->validated());

        return response()->json([
            'message' => 'Responsable registrado exitosamente',
            'data' => $responsable
        ], 201);
    }


    /**
     * Obtener todos los responsables
     */
    public function index()
    {
        $responsables = Responsable::all(); 

        return response()->json([
            'data' => $responsables
        ], 200);
    }


    /**
     * Obtener responsable por id
     */
    public function show($id)
    {
        $responsable = Responsable::find($id);

        if (!$responsable) {
            return response()->json([
                'error' => 'Responsable no encontrado'
            ], 404); 
        }

        return response()->json([
            'data' => $responsable
        ], 200);
    }
}