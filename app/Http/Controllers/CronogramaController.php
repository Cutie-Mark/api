<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Cronograma;
use App\Models\Olimpiada;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


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
            $fechaMinInicio = Carbon::now()->addDays(3)->startOfDay();

            // Validación de los datos de entrada
            $validator = Validator::make($request->all(), [
                'tipo_plazo' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('cronogramas')->where(function ($query) use ($request) {
                        return $query->where('olimpiada_id', $request->olimpiada_id);
                    })
                ],
                'fecha_inicio' => ['required', 'date', 'after_or_equal:' . $fechaMinInicio],
                'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
                'olimpiada_id' => 'required|exists:olimpiadas,id'
            ], [
                'tipo_plazo.unique' => 'Ya existe una fase de ese tipo para la olimpiada seleccionada.',
                'fecha_inicio.after_or_equal' => 'La fecha de inicio debe ser al menos 3 días después de hoy.',
                'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.'
            ]);
    
            if ($validator->fails()) {
                $flatErrors = collect($validator->errors())->flatten()->all();
                return response()->json(['errors' => $flatErrors], 400);            
            }
    
            $olimpiada = Olimpiada::find($request->olimpiada_id);
            $fechaBase = Carbon::parse($request->fecha_inicio);
            $fechaTope = Carbon::parse($request->fecha_fin);

            Log::debug('Fechas recibidas', [
                'fecha_inicio_cronograma' => $fechaBase->toDateTimeString(),
                'fecha_fin_cronograma' => $fechaTope->toDateTimeString(),
                'fecha_inicio_olimpiada' => $olimpiada->fecha_inicio,
                'fecha_fin_olimpiada' => $olimpiada->fecha_fin,
            ]);
            
            if ($fechaBase->lt(Carbon::parse($olimpiada->fecha_inicio)) || $fechaTope->gt(Carbon::parse($olimpiada->fecha_fin))) {
                return response()->json(['message' => 'Las fechas deben estar dentro del periodo de la olimpiada.'], 400);
            }
            
            // Verificar que haya al menos 7 días entre inicio y fin
            if ($fechaBase->diffInDays($fechaTope) < 7) {
                return response()->json(['error' => ['La duración mínima de una fase debe ser de almenos 7 días.']], 400);
            }

            // Crear el cronograma
            $cronograma = Cronograma::create([
                'tipo_plazo' => $request->tipo_plazo,
                'fecha_inicio' => $fechaBase,
                'fecha_fin' => $fechaTope,
                'olimpiada_id' => $request->olimpiada_id
            ]);
    
            return response()->json($cronograma, 201);
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo registrar la fase de olimpiada. Intente nuevamente.'], 500);
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
                'fecha_inicio' => ['required', 'date', 'after_or_equal:' . Carbon::now()->addDays(3)->startOfDay()],
                'fecha_fin' => 'required|date|after:fecha_inicio'            
            ], [
                'fecha_inicio.after_or_equal' => 'La fecha de inicio debe ser al menos 3 días después de hoy.',
                'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.'
            ]);
    
            if ($validator->fails()) {
                $flatErrors = collect($e->errors())->flatten()->all();
                return response()->json(['error' => $flatErrors], 422);  
        
            }
    
            // Obtener el cronograma
            $cronograma = Cronograma::findOrFail($id);
    
    
            $fechaBase = Carbon::parse($request->fecha_inicio);
            $fechaTope = Carbon::parse($request->fecha_fin);
            $olimpiada = $cronograma->olimpiada; // Asume relación belongsTo('olimpiada')
    
            if ($fechaBase->lt(Carbon::parse($olimpiada->fecha_inicio)) || $fechaTope->gt(Carbon::parse($olimpiada->fecha_fin))) {
                return response()->json(['error' => ['Las fechas deben estar dentro del periodo de la olimpiada.']], 400);
            }

            // Validar que dure al menos 7 días
            if ($fechaBase->diffInDays($fechaTope) < 7) {
                return response()->json(['error' => ['La duración mínima del cronograma debe ser de 7 días.']], 400);
            }
    
            $cronograma->update([
                'fecha_inicio' => $fechaBase,
                'fecha_fin' => $fechaTope
            ]);
    
            return response()->json($cronograma, 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo modificar los plazos para la fase de olimpiadas. Intente nuevamente: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cronograma = Cronograma::find($id);
        if (!$cronograma) {
            return response()->json(['error' => 'Cronograma no encontrado'], 404);
        }

        $cronograma->delete();
        return response()->json(['error' => 'Cronograma eliminado']);
    }
}
