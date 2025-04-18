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

    public function store(Request $request)
    {
        $request->validate([
            'codigo_lista' => 'required|string|exists:lista_inscripciones,codigo',
        ], [
            'codigo_lista.required' => 'Debe proporcionar un código de lista.',
            'codigo_lista.exists' => 'No existe ninguna lista con ese código.',
        ]);

        try {
            $codigoLista = $request->input('codigo_lista');

            // Buscar la lista
            $lista = ListaInscripcion::where('codigo_lista', $codigoLista)->firstOrFail();

            // Suponiendo que tiene una relación con inscripciones
            $cantidad = $lista->inscripciones()->count();

            if ($cantidad === 0) {
                return response()->json(['error' => 'La lista no tiene inscripciones asociadas.'], 400);
            }

            $monto = $cantidad * 16.00;

            // Crear la orden
            $orden = OrdenPago::create([
                'codigo_lista' => $codigoLista,
                'monto' => $monto,
                'estado' => 'pendiente',
                'cantidad_inscripciones' => $cantidad
            ]);

            return response()->json([
                'message' => 'Orden de pago generada correctamente.',
                'orden' => $orden
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error al generar orden de pago: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo generar la orden de pago. Intente nuevamente.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
