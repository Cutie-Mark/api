<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;

class DepartamentoController extends Controller
{
    
    /**
     * Mostrar todos los departamentos (solo datos básicos)
     */
    public function index()
    {
        $departamentos = Departamento::select(['id', 'nombre', 'abreviatura'])->get();
        return response()->json($departamentos);
    }
    

    /**
     * Mostrar departamento por ID
     */
    public function show($id)
    {
        try {
            $departamento = Departamento::with('provincias')->findOrFail($id);
            return response()->json($departamento);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Departamento no encontrado'], 404);
        }
    }

    /**
     * Mostrar por abreviatura
     */
    public function showByAbreviatura($abreviatura)
    {
        try {
            $departamento = Departamento::with('provincias')
                ->where('abreviatura', strtoupper($abreviatura))
                ->firstOrFail();
                
            return response()->json($departamento);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Departamento no encontrado'], 404);
        }
    }

    /**
     * Mostrar departamentos con sus provincias
     */
    public function indexWithProvinces()
    {
        $departamentos = Departamento::with(['provincias:id,nombre,departamento_id'])->get();
        
        return response()->json($departamentos->map(function ($departamento) {
            return [
                'id' => $departamento->id,
                'nombre' => $departamento->nombre,
                'abreviatura' => $departamento->abreviatura,
                'provincias' => $departamento->provincias->map(function ($provincia) {
                    return [
                        'id' => $provincia->id,
                        'nombre' => $provincia->nombre
                    ];
                })
            ];
        }));
    }
}