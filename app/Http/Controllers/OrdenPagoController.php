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
            'codigo_lista.required'    => 'El código de lista es obligatorio.',
            'codigo_lista.exists'      => 'Código de lista incorrecto.',
            'nombre_responsable.regex' => 'El nombre_responsable solo debe contener caracteres alfabéticos.',
            'nombre_responsable.max'   => 'El nombre_responsable no debe exceder 60 caracteres.',
            'emitido_por.required'     => 'El campo emitido_por es obligatorio.',
            'emitido_por.max'          => 'El campo emitido_por no debe exceder 60 caracteres.',
            'nitci.required'           => 'El campo nitci es obligatorio.',
            'nitci.regex'              => 'El nitci debe contener solo dígitos y como máximo 10 caracteres.',
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
                // 1. Generar n_orden
                $lastOrden = OrdenPago::orderByDesc('id')->first();
                $nextOrden = $lastOrden ? ((int)$lastOrden->n_orden) + 1 : 1000;
                $n_orden = str_pad((string)$nextOrden, 7, '0', STR_PAD_LEFT);

                // 2. Generar recibo_caja (solo considerando registros ya inicializados)
                $lastRecibo = OrdenPago::whereNotNull('recibo_caja')
                                ->orderByDesc('recibo_caja')
                                ->first();
                $nextRecibo = $lastRecibo
                            ? ((int)$lastRecibo->recibo_caja) + 1
                            : 8941870;

                // 3. Calcular monto
                $precioUnitario = $lista->olimpiada->precio_inscripcion;
                $monto = $cantidad * $precioUnitario;

                // 4. Crear y guardar la orden de pago
                $orden = new OrdenPago();
                $orden->lista_id               = $lista->id;
                $orden->n_orden                = $n_orden;
                $orden->recibo_caja            = $nextRecibo;
                $orden->monto                  = $monto;
                $orden->cantidad_inscripciones = $cantidad;
                $orden->estado                 = 'pendiente';
                $orden->nombre_responsable     = $data['nombre_responsable'];
                $orden->emitido_por            = $data['emitido_por'];
                $orden->nitci                  = $data['nitci'];
                $orden->fecha_emision          = Carbon::now();
                $orden->unidad                 = 'Inscripción';
                $orden->concepto               = 
                    'Inscripcion Olimpiada San Simon acorde a la lista ' . $lista->codigo_lista;
                $orden->save();

                // 5. Actualizar inscripciones y estado de la lista
                Inscripcion::where('lista_id', $lista->id)
                    ->update([
                        'orden_pago_id' => $orden->id,
                        'estado'        => 'Pago Pendiente'
                    ]);
                $lista->estado = 'Pago Pendiente';
                $lista->save();

                // 6. Formatear y retornar la respuesta
                $formatted = $this->formatOrder($orden);

                return response()->json([
                    'mensaje' => 'Orden de pago registrada correctamente.',
                    'orden'   => $formatted
                ], 201);
            });
        } catch (\Throwable $e) {
            Log::error('Error al crear la orden de pago: ' . $e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return response()->json([
                'error'   => 'Error interno al procesar la orden de pago.',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    protected function formatOrder(OrdenPago $orden): array
    {
        // Eager load relaciones necesarias
        $orden->load([
            'lista', 
            'inscripciones.nivelCompetencia.area', 
            'inscripciones.nivelCompetencia.categoria'
        ]);

        $base = [
            'id'                     => $orden->id,
            'n_orden'                => $orden->n_orden,
            'recibo_caja'            => $orden->recibo_caja,
            'fecha_emision'          => $orden->fecha_emision->format('Y-m-d H:i:s'),
            'precio_unitario'        => $orden->lista->olimpiada->precio_inscripcion,
            'monto'                  => $orden->monto,
            'cantidad_inscripciones' => $orden->cantidad_inscripciones,
            'estado'                 => $orden->estado,
            'nombre_responsable'     => $orden->nombre_responsable,
            'emitido_por'            => $orden->emitido_por,
            'nitci'                  => $orden->nitci,
            'codigo_lista'           => $orden->lista->codigo_lista,
            'unidad'                 => $orden->unidad,
            'concepto'               => $orden->concepto,
        ];

        if ($orden->cantidad_inscripciones <= 5) {
            $base['niveles_competencia'] = $orden->inscripciones
                ->map(function($ins) {
                    $nc = $ins->nivelCompetencia;
                    if (! $nc || ! $nc->area || ! $nc->categoria) {
                        return null;
                    }
                    return $nc->area->nombre . ' - ' . $nc->categoria->nombre;
                })
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return $base;
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
        try {
            // 1) Validación con recibo_caja
            $validator = Validator::make($request->all(), [
                'recibo_caja'   => 'required|integer|exists:ordenes_pagos,recibo_caja',
                'codigo_lista'  => 'required|string|exists:listas,codigo_lista',
                'fecha'         => 'required|date',
            ], [
                'recibo_caja.exists'   => 'Número de recibo de caja inválido.',
                'codigo_lista.exists'  => 'Código de lista inválido.',
            ]);

            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return response()->json(['error' => $error], 422);
            }

            $data = $validator->validated();

            // 2) Obtengo lista y orden usando recibo_caja
            $lista = Lista::where('codigo_lista', $data['codigo_lista'])->firstOrFail();
            $orden = OrdenPago::where('recibo_caja', $data['recibo_caja'])
                ->where('lista_id', $lista->id)
                ->firstOrFail();

            $olimpiada = $lista->olimpiada;
            $fechaPago  = Carbon::parse($data['fecha']);

            // 3) Verifico rango de fecha contra la olimpiada
            if ($fechaPago->lt(Carbon::parse($olimpiada->fecha_inicio)) ||
                $fechaPago->gt(Carbon::parse($olimpiada->fecha_fin))) {
                return response()->json([
                    'error' => "La fecha de pago debe estar entre {$olimpiada->fecha_inicio} y {$olimpiada->fecha_fin}."
                ], 422);
            }

            // 4) Transacción para actualizar estados
            DB::transaction(function() use ($orden, $lista, $fechaPago) {
                $orden->estado     = 'pagado';
                $orden->fecha_pago = $fechaPago;
                $orden->save();

                $lista->estado = 'Inscripcion Completa';
                $lista->save();

                Inscripcion::where('lista_id', $lista->id)
                    ->update(['estado' => 'Inscripcion Completa']);
            });

            // 5) Respuesta con formatOrder (incluye recibo_caja)
            return response()->json([
                'mensaje' => 'Pago registrado y estados actualizados correctamente.',
                'orden'   => $this->formatOrder($orden)
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Error al procesar el pago - Modelo no encontrado: ' . $e->getMessage());
            return response()->json([
                'error' => 'El número de factura de la orden de pago es incorrecta.'
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Error de base de datos al procesar el pago: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error en la base de datos al procesar el pago.'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error al procesar el pago: ' . $e->getMessage(), [
                'stack' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Error interno al procesar el pago.',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }
}
