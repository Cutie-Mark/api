<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;

class DepartamentoController extends Controller
{
    
    /**
     * Mostrar todos los departamentos (solo datos básicos)
     */
    public function listar()
    {
        $departamentos = Cache::remember('catalogo_departamentos_base', now()->addDays(7), function () {
            return Departamento::select(['id', 'nombre', 'abreviatura'])->get()->toArray();
        });

        return response()->json($departamentos);
    }
    

    /**
     * Mostrar departamento por ID
     */
    public function mostrar($id)
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
    public function mostrarPorAbreviatura($abreviatura)
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
    public function listarConProvincias()
    {
        $departamentos = Cache::remember('catalogo_departamentos_provincias', now()->addDays(7), function () {
            return Departamento::with(['provincias:id,nombre,departamento_id'])->get()
                ->map(function ($departamento) {
                    return [
                        'id' => $departamento->id,
                        'nombre' => $departamento->nombre,
                        'abreviatura' => $departamento->abreviatura,
                        'provincias' => $departamento->provincias->map(function ($provincia) {
                            return [
                                'id' => $provincia->id,
                                'nombre' => $provincia->nombre
                            ];
                        })->toArray()
                    ];
                })->toArray(); 
        });

        return response()->json($departamentos);
    }
}