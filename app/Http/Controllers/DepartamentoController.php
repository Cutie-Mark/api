<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DepartamentoController extends Controller
{
    // 1. Crear un departamento
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:12|unique:departamentos,nombre',
            'abreviatura' => 'required|string|max:5'
        ]);

        $departamento = Departamento::create($request->all());
        return response()->json($departamento, 201);
    }

    // 2. Mostrar todos los departamentos
    public function index()
    {
        $departamentos = Departamento::with('provincias')->get()
            ->map(function ($departamento) {
                return [
                    'ID' => $departamento->id,
                    'Nombre' => $departamento->nombre,
                    'Provincias' => $departamento->provincias->map(function ($provincia) {
                        return [
                            'Id' => $provincia->id,
                            'nombre' => $provincia->nombre
                        ];
                    })->all()
                ];
            });

        return response()->json($departamentos, 200);
    }

    // 3. Mostrar un departamento por ID
    public function show($id)
    {
        try {
            return response()->json(Departamento::findOrFail($id));
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Departamento no encontrado'], 404);
        }
    }

    // 4. Mostrar por abreviatura
    public function showByAbreviatura($abreviatura)
    {
        try {
            $departamento = Departamento::where('abreviatura', $abreviatura)->firstOrFail();
            return response()->json($departamento);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Departamento no encontrado'], 404);
        }
    }
    /*
    // 5. Mostrar todos con provincias relacionadas
    public function indexWithProvincias() {
        try {
            $departamentos = Departamento::with('provincias')->get();
            return response()->json($departamentos);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }*/
}