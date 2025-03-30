<?php

namespace App\Http\Controllers;

use App\Models\Olimpiada;
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
        // Validación de datos
        $request->validate([
            'nombre' => 'required|string|max:40|unique:olimpiadas',
            'gestion' => 'required|string|max:10',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',

        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'El nombre del evento no puede estar repetido. Ingrese una edicion diferente',
            'gestion.required' => 'La gestión es necesaria.',
            'fecha_inicio.required' => 'La fecha de inicio de inscripcion de la competencia es obligatoria.',
            'fecha_fin.required' => 'La fecha de fin de inscripcion de la competencia es obligatoria.',
            'fecha_fin.after' => 'La fecha de fin de inscripciones debe ser posterior a la fecha de inicio.',
        ]);

        // Crear la olimpiada
        $olimpiada = Olimpiada::create(
                        $request->only(
                            ['nombre', 'gestion', 'fecha_inicio', 'fecha_fin']
                        )
                    );
        return response()->json($olimpiada, 201);
    }

    // Actualizar fechas de inicio o fin
    public function update(Request $request, $id)
    {
        try {
            // Buscar la olimpiada o lanzar un error si no existe
            $olimpiada = Olimpiada::findOrFail($id);

            // Validar que es lo que llega
            $request->validate([
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date',
            ], [
                'fecha_inicio.date' => 'La fecha de inicio debe ser válida.',
                'fecha_fin.date' => 'La fecha de fin debe ser válida.',
            ]);

            // Lógica para la validación de fechas
            if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
                // Si ambas fechas se modifican, validarlas entre sí
                if ($request->fecha_inicio > $request->fecha_fin) {
                    return response()->json([
                        'error' => 'La fecha de inicio no puede ser posterior a la fecha de fin.'
                    ], 422);
                }
            }

            // Validar solo si se cambia una fecha
            if ($request->has('fecha_inicio') && !$request->has('fecha_fin')) {
                // Validar que la nueva fecha de inicio no sea posterior a la fecha fin actual
                if ($request->fecha_inicio > $olimpiada->fecha_fin) {
                    return response()->json([
                        'error' => 'La fecha de inicio no puede ser posterior a la fecha de fin actual.'
                    ], 422);
                }
            }

            if ($request->has('fecha_fin') && !$request->has('fecha_inicio')) {
                // Validar que la nueva fecha de fin no sea anterior a la fecha inicio actual
                if ($request->fecha_fin < $olimpiada->fecha_inicio) {
                    return response()->json([
                        'error' => 'La fecha de fin no puede ser anterior a la fecha de inicio actual.'
                    ], 422);
                }
            }

            // Actualizar las fechas si se proporcionan
            if ($request->has('fecha_inicio')) {
                $olimpiada->fecha_inicio = $request->fecha_inicio;
            }

            if ($request->has('fecha_fin')) {
                $olimpiada->fecha_fin = $request->fecha_fin;
            }

            // Guardar los cambios si se actualizó algo
            if ($olimpiada->isDirty()) {
                $olimpiada->save();
                return response()->json([
                    'message' => 'Fechas actualizadas correctamente.',
                    'olimpiada' => $olimpiada
                ], 200);
            }

            return response()->json(['message' => 'No se realizaron cambios.'], 200);

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
            return response()->json(['message' => 'olimpiada eliminada']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'olimpiada no encontrada'], 404);
        }
    }


    

}
