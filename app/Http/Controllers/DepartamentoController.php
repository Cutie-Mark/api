<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;

class DepartamentoController extends Controller
{
    /**
     * Crear departamento (con validación y formato)
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:12',
            'abreviatura' => 'required|string|max:5'
        ]);

        // Validar nombre único (case-insensitive) y aplicar formato
        $nombreFormateado = ucwords(strtolower(trim($request->nombre)));
        $this->checkNombreUnico($nombreFormateado);

        $departamento = Departamento::create([
            'nombre' => $nombreFormateado,
            'abreviatura' => strtoupper(trim($request->abreviatura))
        ]);

        return response()->json([
            'mensaje' => 'Departamento creado exitosamente',
            'data' => $departamento
        ], 201);
    }

    
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



    /**
     * Valida que el nombre no exista (case-insensitive)
     */
    private function checkNombreUnico(string $nombre, ?int $ignoreId = null): void
    {
        $query = Departamento::whereRaw('LOWER(nombre) = ?', [strtolower($nombre)]);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new HttpResponseException(response()->json([
                'error' => 'Ya existe un departamento con ese nombre'
            ], 409));
        }
    }
}