<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lista;
use App\Models\OrdenPago;
use App\Models\Inscripcion;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;
use PDF;

class OrdenPagoController extends Controller
{
  
    public function generateOrden(string $codigo_lista)
    {
        try {
            $lista = Lista::where('codigo_lista', $codigo_lista)->first();

            if (!$lista) {
                return response()->json(['error' => 'No existe ninguna lista con ese código.'], 404);
            }

            $cantidad = $lista->inscripciones()->count();

            if ($cantidad === 0) {
                return response()->json(['error' => 'La lista no tiene inscripciones asociadas.'], 400);
            }

            // Obtener precio de la olimpiada (asumiendo que está en la lista)
            $precioUnitario = $lista->olimpiada->precio_inscripcion ?? 16.00; // Valor por defecto si no existe

            // Calcular monto automáticamente
            $monto = $cantidad * $precioUnitario;

            return response()->json([
                'codigo_lista' => $codigo_lista,
                'monto' => $monto,
                'estado' => 'pendiente',
                'cantidad_inscripciones' => $cantidad
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al generar orden de pago: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo generar la orden de pago. Intente nuevamente.'], 500);
        }
    }


    public function generateOrdenPorInscripciones(Request $request)
    {
        try {
            $inscripcionIds = $request->input('inscripciones');

            if (!is_array($inscripcionIds) || empty($inscripcionIds)) {
                return response()->json(['error' => 'Debe proporcionar un array de IDs de inscripciones.'], 400);
            }

            // Obtener las inscripciones y validar existencia
            $inscripciones = Inscripcion::whereIn('id', $inscripcionIds)->get();

            if ($inscripciones->isEmpty()) {
                return response()->json(['error' => 'No se encontraron inscripciones válidas.'], 404);
            }

            // Verificar que todas las inscripciones pertenezcan a la misma olimpiada
            $olimpiadaIds = $inscripciones->pluck('olimpiada_id')->unique();

            if ($olimpiadaIds->count() > 1) {
                return response()->json(['error' => 'Las inscripciones no pertenecen a la misma olimpiada.'], 400);
            }

            $olimpiada = Olimpiada::find($olimpiadaIds->first());
            $precioUnitario = $olimpiada->precio_inscripcion ?? 16.00;

            $cantidad = $inscripciones->count();
            $monto = $cantidad * $precioUnitario;

            return response()->json([
                'monto' => $monto,
                'estado' => 'pendiente',
                'cantidad_inscripciones' => $cantidad,
                'olimpiada_id' => $olimpiada->id
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error al generar orden de pago por inscripciones: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo generar la orden de pago. Intente nuevamente.'], 500);
        }
    }



    // Guardar orden con cálculos automáticos (sin depender del frontend)
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'codigo_lista' => 'required|string|exists:listas,codigo_lista',
                'estado' => 'required|in:pendiente,pagado',
                'senior' => 'nullable|string|max:255',
                'emitido_por' => 'required|string|max:255',
                'nitci' => 'required|string|max:10'
            ]);

            // Obtener lista y calcular datos
            $lista = Lista::where('codigo_lista', $request->codigo_lista)->firstOrFail();
            $cantidad = $lista->inscripciones()->count();

            if ($cantidad === 0) {
                return response()->json(['error' => 'La lista no tiene inscripciones.'], 400);
            }

            // Calcular monto basado en la olimpiada asociada
            $precioUnitario = $lista->olimpiada->precio_inscripcion ?? 16.00;
            $monto = $cantidad * $precioUnitario;

            // Crear orden con datos calculados
            $orden = OrdenPago::create([
                'lista_id' => $lista->id,
                'monto' => $monto,
                'estado' => $request->estado,
                'cantidad_inscripciones' => $cantidad,
                'senior' => $request->senior,
                'emitido_por' => $request->emitido_por,
                'nitci' => $request->nitci
            ]);

            Inscripcion::where('lista_id', $lista->id)->update([
                'orden_pago_id' => $orden->id
            ]);

            return response()->json([
                'message' => 'Orden de pago registrada correctamente.',
                'orden' => $orden
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['error' => $e->validator->errors()->first()], 400);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'La lista no existe.'], 404);
        } catch (Exception $e) {
            Log::error('Error al guardar orden de pago: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo registrar la orden de pago. Intente nuevamente.'], 500);
        }
    }


    // Exportar PDF (ya incluye relaciones cargadas)

    public function exportPdf(string $codigo_lista)
    {
        try {
            // 1) Localizo la lista a partir de su código
            $lista = Lista::where('codigo_lista', $codigo_lista)
                        ->firstOrFail();

            // 2) Recupero la ÚLTIMA orden de pago creada para esa lista,
            //    incluyendo todas las relaciones necesarias
            $orden = OrdenPago::with([
                    'lista.inscripciones.postulante',
                    'lista.inscripciones.nivelCompetencia.area',
                    'lista.inscripciones.nivelCompetencia.categoria'
                ])
                ->where('lista_id', $lista->id)
                ->orderByDesc('created_at')    // <-- Aquí nos aseguramos de traer la más reciente
                ->firstOrFail();

            // 3) Generar PDF con la orden más reciente
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ordenes-pdf', compact('orden'));

            return $pdf->download("orden_{$codigo_lista}.pdf");

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Orden o lista no encontrada.'], 404);
        } catch (\Exception $e) {
            Log::error("Error generando PDF: " . $e->getMessage());
            return response()->json(['error' => 'Error interno.'], 500);
        }
    }

//  hola
}

