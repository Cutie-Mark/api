<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Provincia;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DepartamentoController extends Controller
{
    // 1. Obtener todos los departamentos
    public function index()
    {
        return response()->json(Departamento::all());
    }

    // 2. Obtener un departamento por su ID
    public function show($id)
    {
        try {
            $departamento = Departamento::findOrFail($id);
            return response()->json($departamento);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Departamento no encontrado'], 404);
        }
    }

    // 3. Obtener todas las provincias de un departamento
    public function getProvinciasByDepartamento($id)
    {
        try {
            // Buscar el departamento o lanzar un error si no existe
            $departamento = Departamento::findOrFail($id);

            // Obtener las provincias asociadas al departamento
            $provincias = $departamento->provincias;

            return response()->json($provincias);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Departamento no encontrado'], 404);
        }
    }

    // 4. Obtener todos los departamentos con sus provincias
    public function getAllDepartamentosWithProvincias()
    {
        // Obtener todos los departamentos con sus provincias asociadas
        $departamentos = Departamento::with('provincias')->get();

        return response()->json($departamentos);
    }
}
