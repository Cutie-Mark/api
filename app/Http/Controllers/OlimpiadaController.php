<?php

namespace App\Http\Controllers;

use App\Models\Olimpiada;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;


class OlimpiadaController extends Controller
{
    // Obtener todas las olimpiadas
    public function index()
    {
        return response()->json(Olimpiada::all());
    }
    
    // Guardar una olimpiada
    public function store(Request $request)
    {
        try {

            // Obtener la fecha actual y calcular la fecha mínima válida
            $fechaMinimaInicio = Carbon::now()->addDays(3)->startOfDay();

            $validatedData = $request->validate([
                'nombre' => 'required|string|max:40|unique:olimpiadas,nombre',
                'gestion' => 'required|string|max:10',
                'fecha_inicio' => ['required', 'date', 'after_or_equal:' . $fechaMinimaInicio],
                'fecha_fin' => 'required|date|after:fecha_inicio',
            ], [
                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.unique' => 'Este nombre de gestión ya está registrado. Intente con otro.',
                'gestion.required' => 'La gestión es obligatoria.',
                'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
                'fecha_inicio.after_or_equal' => 'La fecha de inicio debe ser al menos 3 días después de hoy.',
                'fecha_fin.required' => 'La fecha de fin es obligatoria.',
                'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            ]);

            // diferencia de 14 días
            $fechaInicio = Carbon::parse($validatedData['fecha_inicio']);
            $fechaFin = Carbon::parse($validatedData['fecha_fin']);

            if ($fechaInicio->diffInDays($fechaFin) < 14) {
                return response()->json(['message' => 'La olimpiada debe durar al menos 14 días.'], 422);
            }

            $olimpiada = Olimpiada::create($validatedData);

            return response()->json([
                'message' => 'La gestión se creó correctamente.',
                'olimpiada' => $olimpiada
            ], 201);

        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);        
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo registrar la gestión. Intente nuevamente.'], 500);
        }
    }

    // Actualizar fechas de inicio o fin
    public function update(Request $request, $id)
    {
        try {
            $olimpiada = Olimpiada::findOrFail($id);

            $validatedData = $request->validate([
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date',
            ]);

            if (isset($validatedData['fecha_inicio']) && isset($validatedData['fecha_fin'])) {
                if ($validatedData['fecha_inicio'] > $validatedData['fecha_fin']) {
                    return response()->json(['error' => 'La fecha de inicio no puede ser posterior a la fecha de fin.'], 422);
                }
            }

            if (isset($validatedData['fecha_inicio']) && !isset($validatedData['fecha_fin'])) {
                if ($validatedData['fecha_inicio'] > $olimpiada->fecha_fin) {
                    return response()->json(['error' => 'La fecha de inicio no puede ser posterior a la fecha de fin actual.'], 422);
                }
            }

            if (isset($validatedData['fecha_fin']) && !isset($validatedData['fecha_inicio'])) {
                if ($validatedData['fecha_fin'] < $olimpiada->fecha_inicio) {
                    return response()->json(['error' => 'La fecha de fin no puede ser anterior a la fecha de inicio actual.'], 422);
                }
            }

            $olimpiada->update($validatedData);

            return response()->json(['message' => 'Fechas actualizadas correctamente.', 'olimpiada' => $olimpiada], 200);
        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Olimpiada no encontrada.'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar la olimpiada.'], 500);
        }
    }

    //  Eliminar una olimpiada por ID
    public function destroy($id)
    {
        try {
            $olimpiada = Olimpiada::findOrFail($id);
            $olimpiada->delete();
            return response()->json(['message' => 'Olimpiada eliminada']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Olimpiada no encontrada'], 404);
        }
    }

    

    

}
