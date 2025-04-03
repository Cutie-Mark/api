<?php

namespace App\Http\Controllers;

use App\Models\Cronograma;
use App\Models\Olimpiada;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CronogramaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Cronograma::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validación de los datos de entrada
            $validator = Validator::make($request->all(), [
                'tipo_plazo' => 'required|string|max:20',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio',
                'olimpiada_id' => 'required|exists:olimpiadas,id'
            ]);
    
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }
    
            $olimpiada = Olimpiada::find($request->olimpiada_id);
            $fechaBase = $request->fecha_inicio;
            $fechaTope = $request->fecha_fin;
    
            if ($fechaBase < $olimpiada->fecha_inicio || $fechaTope > $olimpiada->fecha_fin) {
                return response()->json(['message' => 'Las fechas deben estar dentro del periodo de la olimpiada.'], 400);
            }
    
            // Crear el cronograma
            $cronograma = Cronograma::create([
                'tipo_plazo' => $request->tipo_plazo,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'olimpiada_id' => $request->olimpiada_id
            ]);
    
            return response()->json($cronograma, 201);
    
        } catch (QueryException $e) {
            return response()->json(['message' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error inesperado: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cronograma = Cronograma::find($id);
        if (!$cronograma) {
            return response()->json(['message' => 'Cronograma no encontrado'], 404);
        }
        return response()->json($cronograma);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            // Validación de los datos de entrada
            $validator = Validator::make($request->all(), [
                'tipo_plazo' => 'required|string|max:20',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio',
                'olimpiada_id' => 'required|exists:olimpiadas,id'
            ]);
    
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }
    
            // Obtener el cronograma
            $cronograma = Cronograma::findOrFail($id);
    
            // Obtener la olimpiada
            $olimpiada = Olimpiada::find($request->olimpiada_id);
    
            $fechaBase = $request->fecha_inicio;
            $fechaTope = $request->fecha_fin;
    
            if ($fechaInicio < $olimpiada->fecha_inicio || $fechaFin > $olimpiada->fecha_fin) {
                return response()->json(['message' => 'Las fechas deben estar dentro del periodo de la olimpiada.'], 400);
            }
    
            $cronograma->update([
                'tipo_plazo' => $request->tipo_plazo,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'olimpiada_id' => $request->olimpiada_id
            ]);
    
            return response()->json($cronograma, 200);
    
        } catch (QueryException $e) {
            return response()->json(['message' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error inesperado: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cronograma = Cronograma::find($id);
        if (!$cronograma) {
            return response()->json(['message' => 'Cronograma no encontrado'], 404);
        }

        $cronograma->delete();
        return response()->json(['message' => 'Cronograma eliminado']);
    }
}
