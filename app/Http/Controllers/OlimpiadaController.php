<?php

namespace App\Http\Controllers;

use App\Models\Olimpiada;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * Controlador para la gestión de olimpiadas
 */
class OlimpiadaController extends Controller
{
    // Obtener todas las olimpiadas
    public function index()
    {
        $olimpiadas = Olimpiada::all(); 
        return response()->json($olimpiadas);
    }

    // Obtener olimpiada por ID
    public function show($id)
    {
        $olimpiada = Olimpiada::find($id);
        if (!$olimpiada) {
            return response()->json(['message' => 'Olimpiada no encontrada'], 404);
        }

        return response()->json($olimpiada);
    }

    // Guardar una olimpiada
    public function store(Request $request)
    {
        try {

            // Obtener la fecha actual y calcular la fecha mínima válida
            //$fechaMinimaInicio = Carbon::now()->addDays(3)->startOfDay();

            $validatedData = $request->validate([
                'nombre' => 'required|string|max:40|unique:olimpiadas,nombre',
                'gestion' => 'required|string|max:10',
                'fecha_inicio' => ['required', 'date'],
                'fecha_fin' => 'required|date|after:fecha_inicio',
                'precio_inscripcion' => 'required|numeric|min:0',
                'limite_inscripciones' => 'required|integer|min:1',
                'descripcion_convocatoria' => 'nullable|string',
            ], [
                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.unique' => 'Este nombre de olimpiada ya está registrado. Intente con otro.',
                'gestion.required' => 'La gestión es obligatoria.',
                'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
                //'fecha_inicio.after_or_equal' => 'La fecha de inicio debe ser al menos 3 días después de hoy.',
                'fecha_fin.required' => 'La fecha de fin es obligatoria.',
                'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
                'precio_inscripcion.required' => 'El precio de inscripción es obligatorio.',
                'precio_inscripcion.numeric' => 'El precio debe ser un número.',
                'precio_inscripcion.min' => 'El precio no puede ser negativo.',
                'limite_inscripciones.required' => 'El límite de inscripciones es obligatorio.',
                'limite_inscripciones.integer' => 'El límite debe ser un número entero.',
                'limite_inscripciones.min' => 'El límite debe ser al menos 1.',
            ]);

            // diferencia de 30 días
            $fechaInicio = Carbon::parse($validatedData['fecha_inicio']);
            $fechaFin = Carbon::parse($validatedData['fecha_fin']);

            if ($fechaInicio->diffInDays($fechaFin) < 30) {
                return response()->json(['message' => 'La olimpiada debe durar al menos 30 días.'], 422);
            }

            $olimpiada = Olimpiada::create($validatedData);

            $olimpiada->cronogramas()->create([
                'id_fase' => 1,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => null,
            ]);


            return response()->json([
                'message' => 'La olimpiada se creó correctamente.',
                'olimpiada' => $olimpiada
            ], 201);
        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo registrar la olimpiada, Intente nuevamente.'], 500);
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

            //$fechaMinimaInicio = Carbon::now()->addDays(3)->startOfDay();

            /* if (isset($validatedData['fecha_inicio'])) {
                $nuevaFechaInicio = Carbon::parse($validatedData['fecha_inicio']);
                if ($nuevaFechaInicio->lessThan($fechaMinimaInicio)) {
                    return response()->json([
                        'error' => 'La fecha de inicio debe ser al menos 3 días después de hoy.'
                    ], 422);
                }
            }*/

            $fechaInicio = isset($validatedData['fecha_inicio'])
                ? Carbon::parse($validatedData['fecha_inicio'])
                : Carbon::parse($olimpiada->fecha_inicio);

            $fechaFin = isset($validatedData['fecha_fin'])
                ? Carbon::parse($validatedData['fecha_fin'])
                : Carbon::parse($olimpiada->fecha_fin);

            // Validar que la fecha de inicio no sea posterior a la de fin
            if ($fechaInicio->gt($fechaFin)) {
                return response()->json(['error' => 'La fecha de inicio no puede ser posterior a la fecha de fin.'], 422);
            }

            // Validar duración mínima de 30 días
            if ($fechaInicio->diffInDays($fechaFin) < 30) {
                return response()->json(['error' => 'La olimpiada debe durar al menos 30 días.'], 422);
            }

            $olimpiada->update($validatedData);

            return response()->json([
                'message' => 'Fechas actualizadas correctamente.'
            ], 200);
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

    public function checkOlimpiadaEnCurso()
    {
        $hoy = now()->toDateString();

        $olimpiadas = Olimpiada::where('fecha_inicio', '<=', $hoy)
            ->where('fecha_fin', '>=', $hoy)
            ->get();

        if ($olimpiadas->isEmpty()) {
            return response()->json(['message' => 'No hay olimpiada vigente'], 200);
        }

        $resultado = $olimpiadas->map(function ($olimpiada) {
            return [
                'id' => $olimpiada->id,
                'nombre' => $olimpiada->nombre,
                'fecha_inicio' => $olimpiada->fecha_inicio,
                'fecha_fin' => $olimpiada->fecha_fin,
                'gestion' => $olimpiada->gestion,
                'url_plantilla' => $olimpiada->url_plantilla,
                'limite_inscripciones' => $olimpiada->limite_inscripciones,
                'precio_inscripcion' => $olimpiada->precio_inscripcion,
                'fase_actual' => $olimpiada->fase, 
            ];
        });

        return response()->json($resultado, 200);
    }

    public function getOlimpiadaWithFaseEnCurso($id)
    {
        try {
            $hoy = now();

            $olimpiada = Olimpiada::findOrFail($id);

            $data = [
                'id' => $olimpiada->id,
                'nombre' => $olimpiada->nombre,
                'fecha_inicio' => $olimpiada->fecha_inicio,
                'fecha_fin' => $olimpiada->fecha_fin,
                'gestion' => $olimpiada->gestion,
                //'url_plantilla' => $olimpiada->url_plantilla,
                'limite_inscripciones' => $olimpiada->limite_inscripciones,
                'precio_inscripcion' => $olimpiada->precio_inscripcion,
            ];

            $fase = $olimpiada->cronogramas()
                ->where('fecha_inicio', '<=', $hoy)
                ->where('fecha_fin', '>=', $hoy)
                ->first();

            if ($fase) {
                $data['fase_actual'] = $fase;
            }

            return response()->json($data, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Olimpiada no encontrada'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener la olimpiada'], 500);
        }
    }


    public function getOlimpiadaWithCronogramas($id)
    {
        try {
            $olimpiada = Olimpiada::with(['cronogramas' => function ($query) {
                $query->orderBy('id_fase');
            }])->findOrFail($id);

            return response()->json([
                'olimpiada' => $olimpiada
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Olimpiada no encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la olimpiada.'
            ], 500);
        }
    }

    public function showUrlPlantilla($id)
    {
        $olimpiada = Olimpiada::findOrFail($id);

        return response()->json([
            'url_plantilla' => $olimpiada->url_plantilla
        ]);
    }

    public function uploadExcelFormato(Request $request)
    {
        // 1) Validación básica
        $data = $request->validate([
            'olimpiadaId'         => 'required|integer|exists:olimpiadas,id',
            'fileName'            => 'required|string|max:255',
            'fileContentBase64'   => 'required|string',
        ]);

        // 2) Buscar la olimpiada
        $olimpiada = Olimpiada::findOrFail($data['olimpiadaId']);

        // 3) Extraer y decodificar Base64
        $base64 = $data['fileContentBase64'];
        if (strpos($base64, ';base64,') !== false) {
            [$meta, $base64] = explode(';base64,', $base64);
        }

        $fileData = base64_decode($base64);
        if ($fileData === false) {
            return response()->json(['error' => 'Base64 inválido'], 422);
        }

        // 4) Generar ruta y guardar archivo
        $folder = "uploads/olimpiadas/{$olimpiada->id}";
        $path = "{$folder}/{$data['fileName']}";

        try {
            Storage::disk('public')->put($path, $fileData);

            // 5) Actualizar la olimpiada con la nueva ruta
            $olimpiada->update([
                'url_plantilla' => $path
            ]);

            return response()->json([
                'success' => true,
                'path'    => $path,
                'url' => asset('storage/' . $path),
                'olimpiada' => $olimpiada->fresh() // Devuelve los datos actualizados
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al guardar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadExcelFormato($olimpiadaId)
    {
        try {
            $olimpiada = Olimpiada::findOrFail($olimpiadaId);

            if (!$olimpiada->url_plantilla) {
                return response()->json(['error' => 'Archivo no encontrado'], 404);
            }

            $filePath = Storage::disk('local')->path($olimpiada->url_plantilla);

            if (!Storage::disk('local')->exists($olimpiada->url_plantilla)) {
                return response()->json(['error' => 'El archivo no existe'], 404);
            }

            // Usa response()->download() en lugar de Storage::download()
            return response()->download(
                $filePath,
                basename($olimpiada->url_plantilla)
            );
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Olimpiada no encontrada'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al descargar el archivo'], 500);
        }
    }

    /**
     * Eliminar url_plantilla de todas las olimpiadas
     */
    public function clearAllPlantillas()
    {
        $paths = Olimpiada::whereNotNull('url_plantilla')->pluck('url_plantilla')->filter();
        try {
            foreach ($paths as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
            Olimpiada::query()->update([
                'url_plantilla' => null,
                'descripcion_convocatoria' => DB::raw("COALESCE(descripcion_convocatoria, '')")
            ]);
            return response()->json([
                'message' => 'Se han borrado las plantillas de todas las olimpiadas.',
                'deleted_files' => $paths->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar las plantillas de todas las olimpiadas.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
    public function getOlimpiadasByFases(Request $request)
    {
        try {

            $validatedData = $request->validate([
                'fases' => 'required|array',
                'fases.*' => 'string'
            ], [
                'fases.required' => 'Debe proporcionar un array de fases',
                'fases.array' => 'El parámetro fases debe ser un array',
                'fases.*.string' => 'Los nombres de las fases deben ser cadenas de texto'
            ]);

            $fases = $validatedData['fases'];
            $hoy = now();

            $olimpiadas = Olimpiada::whereHas('cronogramas', function ($query) use ($fases, $hoy) {
                $query->whereHas('fase', function ($q) use ($fases) {
                    $q->whereIn('nombre_fase', $fases);
                })
                ->where('fecha_inicio', '<=', $hoy)
                ->where(function($q) use ($hoy) {
                    $q->where('fecha_fin', '>', $hoy)
                      ->orWhereNull('fecha_fin');
                });
            })
            ->with(['cronogramas' => function ($query) use ($fases, $hoy) {
                $query->whereHas('fase', function ($q) use ($fases) {
                    $q->whereIn('nombre_fase', $fases);
                })
                ->where('fecha_inicio', '<=', $hoy)
                ->where(function($q) use ($hoy) {
                    $q->where('fecha_fin', '>', $hoy)
                      ->orWhereNull('fecha_fin');
                });
            }])
            ->get();


            if ($olimpiadas->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron olimpiadas con las fases especificadas que estén activas actualmente'
                ], 404);
            }

            return response()->json($olimpiadas, 200);
        } catch (ValidationException $e) {
            $flatErrors = collect($e->errors())->flatten()->all();
            return response()->json(['error' => $flatErrors], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener las olimpiadas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getOlimpiadasPasadas()
    {
        try {
            $hoy = now();
            $olimpiadas = Olimpiada::where('fecha_fin', '<', $hoy)->get();

            return response()->json($olimpiadas, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener las olimpiadas pasadas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getOlimpiadasFuturas()
    {
        try {
            $hoy = now();
            $olimpiadas = Olimpiada::where('fecha_inicio', '>', $hoy)->get();

            return response()->json($olimpiadas, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener las olimpiadas futuras: ' . $e->getMessage()
            ], 500);
        }
    }
}
