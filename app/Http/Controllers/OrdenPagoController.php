<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
            $lista = ListaInscripcion::where('codigo_lista', $codigo_lista)->first();
    
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
    
        } catch (\Exception $e) {
            \Log::error('Error al generar orden de pago: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo generar la orden de pago. Intente nuevamente.'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = validator($request->all(), [
                'codigo_lista' => 'required|string|exists:listas,codigo_lista',
                'monto' => 'required|numeric|min:0',
                //'estado' => 'required|in:pendiente,pagado',
                'cantidad_inscripciones' => 'required|integer|min:1',
                'senior' => 'nullable|string|max:255',
                'emitido_por' => 'required|string|max:255',
                'nitci' => 'required|string|size:7'
            ]);

            if ($validated->fails()) {
                return response()->json(['error' => $validated->errors()->first()], 400);
            }

            $orden = OrdenPago::create($request->only([
                'codigo_lista',
                'monto',
                'estado',
                'cantidad_inscripciones',
                'senior',
                'emitido_por',
                'nitci'
            ]));

            // Aca luego le pongo las inscripciones, quiza cambie


            return response()->json([
                'message' => 'Orden de pago registrada correctamente.',
                'orden' => $orden
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al guardar orden de pago: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo registrar la orden de pago. Intente nuevamente.'], 500);
        }
    }

    public function showByCodigoLista(string $codigo_lista)
    {
        try {
            $orden = OrdenPago::where('codigo_lista', $codigo_lista)
                ->orderBy('fecha_emision', 'desc')
                ->first();
    
            if (!$orden) {
                return response()->json(['error' => 'No se encontró ninguna orden de pago para este código.'], 404);
            }
    
            return response()->json(['orden' => $orden], 200);
    
        } catch (\Exception $e) {
            \Log::error('Error al buscar orden de pago: ' . $e->getMessage());
            return response()->json(['error' => 'Hubo un error al buscar la orden de pago.'], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
