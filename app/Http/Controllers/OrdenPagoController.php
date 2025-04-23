<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lista;
use App\Models\OrdenPago;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;
use PDF;

class OrdenPagoController extends Controller
{
    // ... (método index sin cambios)

    // Generar datos previos para la orden (calcula automáticamente)
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

    // Guardar orden con cálculos automáticos (sin depender del frontend)
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'codigo_lista' => 'required|string|exists:listas,codigo_lista',
                'estado' => 'required|in:pendiente,pagado',
                'senior' => 'nullable|string|max:255',
                'emitido_por' => 'required|string|max:255',
                'nitci' => 'required|string|size:7'
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

    // Mostrar orden por código de lista (con cálculos actualizados)
    public function showByCodigoLista(string $codigo_lista)
    {
        try {
            $lista = Lista::where('codigo_lista', $codigo_lista)->firstOrFail();
            $orden = OrdenPago::where('lista_id', $lista->id)->latest('fecha_emision')->first();

            if (!$orden) {
                return response()->json(['error' => 'No se encontró ninguna orden de pago.'], 404);
            }

            // Actualizar datos si hay cambios (opcional, depende de si se permiten modificaciones)
            $cantidadActual = $lista->inscripciones()->count();
            $precioUnitario = $lista->olimpiada->precio_inscripcion ?? 16.00;

            $orden->update([
                'cantidad_inscripciones' => $cantidadActual,
                'monto' => $cantidadActual * $precioUnitario
            ]);

            return response()->json(['orden' => $orden], 200);

        } catch (Exception $e) {
            Log::error('Error al buscar orden de pago: ' . $e->getMessage());
            return response()->json(['error' => 'Hubo un error al buscar la orden de pago.'], 500);
        }
    }

    // Exportar PDF (ya incluye relaciones cargadas)
    public function exportPdf(string $codigo_lista)
    {
        try {
            $orden = OrdenPago::with([
                'lista.inscripciones.postulante',
                'lista.inscripciones.nivelCompetencia.area',
                'lista.inscripciones.nivelCompetencia.categoria'
            ])->whereHas('lista', fn($q) => $q->where('codigo_lista', $codigo_lista))
              ->firstOrFail();
    

            // Generar PDF con datos actualizados
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ordenes-pdf', compact('orden'));
            return $pdf->download("orden_{$codigo_lista}.pdf");

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Orden no encontrada.'], 404);
        } catch (Exception $e) {
            Log::error("Error generando PDF: " . $e->getMessage());
            return response()->json(['error' => 'Error interno.'], 500);
        }
    }
}