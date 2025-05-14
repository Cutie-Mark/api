<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Lista;
use App\Models\OrdenPago;
use App\Models\Inscripcion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OrdenPagoController extends Controller
{
    public function index()
    {
        $orders = OrdenPago::all()->map(fn($order) => $this->formatOrder($order));
        return response()->json($orders, 200);
    }

    public function showByCodLista(string $codigo_lista)
    {
        $lista = Lista::where('codigo_lista', $codigo_lista)->first();
        if (! $lista) {
            return response()->json(['error' => 'Código de lista no encontrado.'], 404);
        }

        $orden = OrdenPago::where('lista_id', $lista->id)->orderByDesc('created_at')->first();
        if (! $orden) {
            return response()->json(['error' => 'No existe orden de pago para la lista dada.'], 404);
        }
        return response()->json($this->formatOrder($orden), 200);
    }

    public function showByNOrden(string $n_orden)
    {
        $orden = OrdenPago::where('n_orden', $n_orden)->first();
        if (! $orden) {
            return response()->json(['error' => 'Número de orden no encontrado.'], 404);
        }
        return response()->json($this->formatOrder($orden), 200);
    }

    public function store(Request $request)
    {
        $rules = [
            'codigo_lista'       => 'required|string|exists:listas,codigo_lista',
            'nombre_responsable' => ['required','string','max:60','regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/'],
            'emitido_por'        => 'required|string|max:60',
            'nitci'              => ['required','regex:/^[0-9]{1,10}$/'],
        ];
        $messages = [
            'codigo_lista.required'       => 'El código de lista es obligatorio.',
            'codigo_lista.exists'         => 'Código de lista incorrecto.',
            'nombre_responsable.regex'    => 'El nombre_responsable solo debe contener caracteres alfabéticos.',
            'nombre_responsable.max'      => 'El nombre_responsable no debe exceder 60 caracteres.',
            'emitido_por.required'        => 'El campo emitido_por es obligatorio.',
            'emitido_por.max'             => 'El campo emitido_por no debe exceder 60 caracteres.',
            'nitci.required'              => 'El campo nitci es obligatorio.',
            'nitci.regex'                 => 'El nitci debe contener solo dígitos y como máximo 10 caracteres.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = $validator->validated();

        $lista = Lista::where('codigo_lista', $data['codigo_lista'])->first();
        

        $cantidad = $lista->inscripciones()->count();
        if ($cantidad === 0) {
            return response()->json(['error' => 'La lista no tiene inscripciones.'], 400);
        }

        try {
            return DB::transaction(function() use ($data, $lista, $cantidad) {
                $last = OrdenPago::orderByDesc('id')->first();
                $next = $last ? ((int)$last->n_orden) + 1 : 1000;
                $n_orden = str_pad((string)$next, 6, '0', STR_PAD_LEFT);

                $precioUnitario = 15.00;
                $monto = $cantidad * $precioUnitario;

                $orden = new OrdenPago();
                $orden->lista_id = $lista->id;
                $orden->n_orden = $n_orden;
                $orden->monto = $monto;
                $orden->cantidad_inscripciones = $cantidad;
                $orden->estado = 'pendiente';
                $orden->nombre_responsable = $data['nombre_responsable'];
                $orden->emitido_por = $data['emitido_por'];
                $orden->nitci = $data['nitci'];
                $orden->fecha_emision = Carbon::now();
                $orden->unidad = "Inscripción";
                $orden->concepto = "Inscripcion Olimpiada San Simon acorde a la lista " . $lista->codigo_lista;
                $orden->save();

                Inscripcion::where('lista_id', $lista->id)->update(['orden_pago_id' => $orden->id]);

                return response()->json([
                    'mensaje' => 'Orden de pago registrada correctamente.',
                    'orden'   => $this->formatOrder($orden)
                ], 201);
            });
        } catch (\Throwable $e) {
            Log::error('Error al crear la orden de pago: ' . $e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Error interno al procesar la orden de pago.', 'detalle' => $e->getMessage()], 500);
        }
    }


    private function formatOrder(OrdenPago $orden): array
    {
        $fecha = optional($orden->fecha_emision)->toDateTimeString() ?: $orden->created_at->toDateTimeString();
        return [
            'id'                     => $orden->id,
            'n_orden'                => $orden->n_orden,
            'fecha_emision'          => $fecha,
            'precio_unitario'        => $orden->precio_unitario,
            'monto'                  => $orden->monto,
            'cantidad_inscripciones' => $orden->cantidad_inscripciones,
            'estado'                 => $orden->estado,
            'nombre_responsable'     => $orden->nombre_responsable,
            'emitido_por'            => $orden->emitido_por,
            'nitci'                  => $orden->nitci,
            'codigo_lista'           => $orden->lista->codigo_lista,
            'unidad'                 => $orden->unidad,
            'concepto'               => $orden->concepto,
            'niveles_competencia'    => $orden->niveles_competencia,
        ];
    }

    public function datosPrevios(string $codigo_lista)
    {
        $lista = Lista::where('codigo_lista', $codigo_lista)->first();

        if (! $lista) {
            return response()->json(['error' => 'Código de lista no encontrado.'], 404);
        }

        $cantidad = $lista->inscripciones()->count();
        $monto = $cantidad * 15.00;

        // Verificamos si ya hay una orden generada
        $orden = OrdenPago::where('lista_id', $lista->id)->first();
        $estado = $orden ? 'pendiente' : 'sin orden';

        return response()->json([
            'codigo_lista'           => $lista->codigo_lista,
            'monto'                  => round($monto, 2),
            'estado'                 => $estado,
            'cantidad_inscripciones' => $cantidad
        ], 200);
    }

    public function pagar(Request $request)
    {
        // 1) Valido que venga n_orden, código de lista y fecha
        $data = $request->validate([
            'n_orden'     => 'required|string|exists:ordenes_pagos,n_orden',
            'codigo_lista'=> 'required|string|exists:listas,codigo_lista',
            'fecha'       => 'required|date',
        ]);

        // 2) Busco la lista por código
        $lista = Lista::where('codigo_lista', $data['codigo_lista'])->firstOrFail();

        // 3) Aseguro que la orden exista y pertenezca a esa lista
        $orden = OrdenPago::where('n_orden', $data['n_orden'])
            ->where('lista_id', $lista->id)
            ->firstOrFail();

        $olimpiada  = $lista->olimpiada;
        $fechaPago  = Carbon::parse($data['fecha']);

        // 4) Verifico rango de fecha
        if ($fechaPago->lt(Carbon::parse($olimpiada->fecha_inicio))
            || $fechaPago->gt(Carbon::parse($olimpiada->fecha_fin))) {
            return response()->json([
                'error' => "La fecha de pago debe estar entre {$olimpiada->fecha_inicio} y {$olimpiada->fecha_fin}."
            ], 422);
        }

        // 5) Transacción para actualizar estados
        DB::transaction(function() use ($orden, $lista, $fechaPago) {
            // a) Marco la orden como pagada
            $orden->estado     = 'pagado';
            $orden->fecha_pago = $fechaPago;
            $orden->save();

            // b) Marco la lista como inscripción completa
            $lista->estado = 'Inscripcion Completa';
            $lista->save();

            // c) Marco todas las inscripciones de esa lista
            Inscripcion::where('lista_id', $lista->id)
                ->update(['estado' => 'Inscripcion Completa']);
        });

        // 6) Respuesta con mensaje y detalle de la orden
        return response()->json([
            'mensaje' => 'Pago registrado y estados actualizados correctamente.',
            'orden'   => $this->formatOrder($orden)
        ], 200);
    }


}
