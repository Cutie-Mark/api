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
                'tipo_plazo' => ['required', 'string', 'max:20'],
                'fecha_inicio' => ['required', 'date', 'after_or_equal:' . $fechaMinInicio],
                'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
                'olimpiada_id' => 'required|exists:olimpiadas,id'
            ], [
                'fecha_inicio.after_or_equal' => 'La fecha de inicio debe ser al menos 3 días después de hoy.',
                'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.'
            ]);
    
            // Si la validación falla, respondemos con los errores
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()->flatten()->all()], 400);
            }
            
            // Verificamos si ya existe un cronograma con el mismo tipo_plazo para la misma olimpiada
            $existingCronograma = Cronograma::where('olimpiada_id', $request->olimpiada_id)
                                                ->where('tipo_plazo', $request->tipo_plazo)
                                                ->first();

            if ($existingCronograma) {
            return response()->json(['error' => 'Ya existe una fase de ese tipo para la olimpiada seleccionada.'], 400);
            }

            $olimpiada = Olimpiada::findOrFail($request->olimpiada_id);
            $fechaBase = Carbon::parse($request->fecha_inicio);
            $fechaTope = Carbon::parse($request->fecha_fin);
            
            if ($fechaBase->lt($olimpiada->fecha_inicio) || $fechaTope->gt($olimpiada->fecha_fin)) {
                return response()->json(['error' => 'Las fechas deben estar dentro del periodo de la olimpiada.'], 400);
            }
            
            // Verificar que haya al menos 7 días entre inicio y fin
           /* if ($fechaBase->diffInDays($fechaTope) < 7) {
                return response()->json(['error' => ['La duración mínima de una fase debe ser de almenos 7 días.']], 400);
            }*/

            // Verificar que no se solapen fechas con otros cronogramas de la misma olimpiada
            $choqueCronograma = Cronograma::where('olimpiada_id', $request->olimpiada_id)
            ->where(function ($query) use ($fechaBase, $fechaTope) {
                $query->where(function ($q) use ($fechaBase, $fechaTope) {
                    $q->where('fecha_inicio', '<=', $fechaTope)
                    ->where('fecha_fin', '>=', $fechaBase);
                });
            })
            ->first();

            if ($choqueCronograma) {
            return response()->json(['error' => 'Las fechas se solapan con otra fase ya registrada para esta olimpiada.'], 400);
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
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'            
            ], [
                'fecha_inicio.after_or_equal' => 'La fecha de inicio debe ser al menos 3 días después de hoy.',
                'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior a la fecha de inicio.'
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

            /*// Validar que dure al menos 7 días
            if ($fechaBase->diffInDays($fechaTope) < 7) {
                return response()->json(['error' => ['La duración mínima del cronograma debe ser de 7 días.']], 400);
            }*/
    
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


    public function createOlimpiadaFases(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_olimpiada' => 'required|exists:olimpiadas,id',
                'cronogramas' => 'required|array|size:6',
                'cronogramas.*.tipo_plazo' => 'required|string',
                'cronogramas.*.fecha_inicio' => 'required|date',
                'cronogramas.*.fecha_fin' => 'required|date'
            ], [
                //'cronogramas.size' => 'Se deben enviar exactamente 6 fases para la olimpiada.',
                //'cronogramas.*.fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.'
            ]);
    
            if ($validator->fails()) {
                $flatErrors = collect($validator->errors())->flatten()->all();
                return response()->json(['error' => $flatErrors], 422);
            }
    
            $idOlimpiada = $request->input('id_olimpiada');
            $cronogramas = $request->input('cronogramas');

            $olimpiada = Olimpiada::findOrFail($idOlimpiada);
            $inicioOlimpiada = Carbon::parse($olimpiada->fecha_inicio);
            $finOlimpiada = Carbon::parse($olimpiada->fecha_fin);

            $fechasFases = collect($cronogramas)->map(function ($item) {
                return [
                    'tipo_plazo' => $item['tipo_plazo'],
                    'fecha_inicio' => Carbon::parse($item['fecha_inicio']),
                    'fecha_fin' => Carbon::parse($item['fecha_fin'])
                ];
            })->sortBy('fecha_inicio')->values();

            
            foreach ($fechasFases as $fase) {
                if ($fase['fecha_inicio']->lt($inicioOlimpiada) || $fase['fecha_fin']->gt($finOlimpiada)) {
                    return response()->json(['error' => ["Las fechas para '{$fase['tipo_plazo']}' deben estar dentro del rango de la olimpiada."]], 400);
                }
    
                /*if ($fase['fecha_fin']->lt($fase['fecha_inicio'])) {
                    return response()->json(['error' => ["La fecha de fin debe ser igual o posterior a la fecha de inicio para '{$fase['tipo_plazo']}'"]], 400);
                }*/
            }

            Cronograma::where('olimpiada_id', $idOlimpiada)->delete();
    
            foreach ($fechasFases as $fase) {
                Cronograma::create([
                    'olimpiada_id' => $idOlimpiada,
                    'tipo_plazo' => $fase['tipo_plazo'],
                    'fecha_inicio' => $fase['fecha_inicio'],
                    'fecha_fin' => $fase['fecha_fin']
                ]);
            }
    //hola
            return response()->json(['message' => 'Cronogramas creados / actualizados correctamente'], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo registrar los cronogramas. Intente nuevamente: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createFasesOfOlimpiada(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_olimpiada' => 'required|exists:olimpiadas,id',
                'id_fases' => 'required|array|min:1',
                'id_fases.*' => 'required|exists:fases,id'
            ]);
    
            if ($validator->fails()) {
                $flatErrors = collect($validator->errors())->flatten()->all();
                return response()->json(['error' => $flatErrors], 422);
            }
    
            $idOlimpiada = $request->input('id_olimpiada');
            $idFases = $request->input('id_fases');
    
            $cronogramas = [];
    
            foreach ($idFases as $idFase) {
                $fase = \App\Models\Fase::find($idFase);
                $tipoPlazo = $fase ? $fase->nombre_fase : '';

                $cronograma = Cronograma::firstOrCreate([
                    'olimpiada_id' => $idOlimpiada,
                    'id_fase' => $idFase,
                ], [
                    'tipo_plazo' => $tipoPlazo,
                    'fecha_inicio' => null,
                    'fecha_fin' => null
                ]);
    
                $cronogramas[] = $cronograma;
            }
    
            return response()->json([
                'message' => 'Fases ligadas a una olimpiada correctamente.',
                'data' => $cronogramas
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al ligar fases a una olimpiada: '
            ], 500);
        }
    }

    public function syncFasesOfOlimpiada(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_olimpiada' => 'required|exists:olimpiadas,id',
                'fases_agregar' => 'nullable|array',
                'fases_agregar.*' => 'required|exists:fases,id',
                'fases_borrar' => 'nullable|array',
                'fases_borrar.*' => 'required|exists:fases,id'
            ]);

            if ($validator->fails()) {
                $flatErrors = collect($validator->errors())->flatten()->all();
                return response()->json(['error' => $flatErrors], 422);
            }

            $idOlimpiada = $request->input('id_olimpiada');
            $fasesAgregar = $request->input('fases_agregar', []);
            $fasesBorrar = $request->input('fases_borrar', []);

            $agregados = [];
            $borrados = [];

            // Agregar fases
            foreach ($fasesAgregar as $idFase) {
                /*
                $fase = \App\Models\Fase::find($idFase);
                $tipoPlazo = $fase ? $fase->nombre_fase : '';
                */
                $cronograma = Cronograma::firstOrCreate(
                    [
                        'olimpiada_id' => $idOlimpiada,
                        'id_fase' => $idFase
                    ],
                    [
                        //'tipo_plazo' => $tipoPlazo,
                        'fecha_inicio' => null,
                        'fecha_fin' => null
                    ]
                );

                if ($cronograma->wasRecentlyCreated) {
                    $agregados[] = $cronograma;
                }
            }

            // Borrar fases
            foreach ($fasesBorrar as $idFase) {
                $cronograma = Cronograma::where('olimpiada_id', $idOlimpiada)
                    ->where('id_fase', $idFase)
                    ->first();

                if ($cronograma) {
                    $cronograma->delete();
                    $borrados[] = ['id' => $cronograma->id, 'id_fase' => $idFase];
                }
            }

            return response()->json([
                'message' => 'Sincronización de fases completada.',
                'agregados' => $agregados,
                'borrados' => $borrados
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al sincronizar fases: ' . $e->getMessage()
            ], 500);
        }
    }



    
    public function completeCronogramas(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cronogramas' => 'required|array|min:1',
                'cronogramas.*.id' => 'required|exists:cronogramas,id',
                'cronogramas.*.fecha_inicio' => 'required|date',
                'cronogramas.*.fecha_fin' => 'required|date|after_or_equal:cronogramas.*.fecha_inicio'
            ], [
                'cronogramas.*.fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.'
            ]);

            if ($validator->fails()) {
                $flatErrors = collect($validator->errors())->flatten()->all();
                return response()->json(['error' => $flatErrors], 422);
            }

            $cronogramasData = $request->input('cronogramas');

            $actualizados = [];

            foreach ($cronogramasData as $data) {
                $cronograma = Cronograma::findOrFail($data['id']);
                $olimpiada = $cronograma->olimpiada;

                $fechaInicio = Carbon::parse($data['fecha_inicio']);
                $fechaFin = Carbon::parse($data['fecha_fin']);

                if ($fechaInicio->lt(Carbon::parse($olimpiada->fecha_inicio)) || $fechaFin->gt(Carbon::parse($olimpiada->fecha_fin))) {
                    return response()->json(['error' => ["Las fechas del cronograma ID {$cronograma->id} deben estar dentro del periodo de la olimpiada."]], 400);
                }

                $cronograma->update([
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin
                ]);

                $actualizados[] = $cronograma;
            }

            return response()->json([
                'message' => 'Fechas actualizadas correctamente para los cronogramas.',
                'data' => $actualizados
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar fechas de cronogramas: ' . $e->getMessage()
            ], 500);
        }
    }
}
