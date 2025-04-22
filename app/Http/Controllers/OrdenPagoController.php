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
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    // Función para obtener los datos necesarios antes de guardar la orden
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
    
            // Calcular el monto
            $monto = $cantidad * 16.00;
    
            // Retornar los datos de la orden de pago (sin crearla todavía)
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

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'codigo_lista' => 'required|string|exists:listas,codigo_lista',
                'monto' => 'required|numeric|min:0',
                'estado' => 'required|in:pendiente,pagado',
                'cantidad_inscripciones' => 'required|integer|min:1',
                'senior' => 'nullable|string|max:255',
                'emitido_por' => 'required|string|max:255',
                'nitci' => 'required|string|size:7'
            ]);

            // Obtener la lista relacionada
            $lista = Lista::where('codigo_lista', $request->codigo_lista)->firstOrFail();

            // Crear la orden con el lista_id correcto
            $orden = OrdenPago::create([
                'lista_id' => $lista->id,
                'monto' => $request->monto,
                'estado' => $request->estado,
                'cantidad_inscripciones' => $request->cantidad_inscripciones,
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

    public function showByCodigoLista(string $codigo_lista)
    {
        try {
            $lista = Lista::where('codigo_lista', $codigo_lista)->firstOrFail();
            $orden = OrdenPago::where('lista_id', $lista->id)->orderBy('fecha_emision', 'desc')->first();
    
            if (!$orden) {
                return response()->json(['error' => 'No se encontró ninguna orden de pago para este código.'], 404);
            }
    
            return response()->json(['orden' => $orden], 200);
    
        } catch (Exception $e) {
            Log::error('Error al buscar orden de pago: ' . $e->getMessage());
            return response()->json(['error' => 'Hubo un error al buscar la orden de pago.'], 500);
        }
    }

    /**
     * Exportar orden de pago a PDF
     */
    public function exportPdf(string $codigo_lista) {
        ini_set('memory_limit', '256M');
        set_time_limit(0); // Desactiva el límite de tiempo
    
        try {
            $orden = OrdenPago::with('lista', 'inscripciones.postulante')
                ->whereHas('lista', fn($q) => $q->where('codigo_lista', $codigo_lista))
                ->firstOrFail();
    
            // Opcional: Verificar la vista HTML primero
            // return view('ordenes-pdf', compact('orden'));
    
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