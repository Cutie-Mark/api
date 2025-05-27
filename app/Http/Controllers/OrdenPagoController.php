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
use App\Http\Requests\PagarOrdenRequest;
use App\Services\OrdenPagoService;

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

                // 2. Calcular monto
                $precioUnitario = $lista->olimpiada->precio_inscripcion;
                $monto = $cantidad * $precioUnitario;

                // 4. Crear y guardar la orden de pago
                $orden = new OrdenPago();
                $orden->lista_id               = $lista->id;
                $orden->n_orden                = $n_orden;
                $orden->monto                  = $monto;
                $orden->cantidad_inscripciones = $cantidad;
                $orden->estado                 = 'pendiente';
                $orden->nombre_responsable     = $data['nombre_responsable'];
                $orden->emitido_por            = $data['emitido_por'];
                $orden->nitci                  = $data['nitci'];
                $orden->fecha_emision          = Carbon::now();
                $orden->unidad = 'Inscripción';
                
                // Construir el concepto base
                $concepto = 'Inscripción Olimpiada San Simón acorde a la lista ' . $lista->codigo_lista;
                
                // Si hay menos de 5 inscripciones, agregar los niveles de competencia
                if ($cantidad <= 5) {
                    $concepto .= "\n\nNIVELES DE COMPETENCIA:";
                    $nivelesCompetencia = $lista->inscripciones()
                        ->with(['nivelCompetencia.area', 'nivelCompetencia.categoria'])
                        ->get()
                        ->map(function($ins) {
                            $nc = $ins->nivelCompetencia;
                            if (!$nc || !$nc->area || !$nc->categoria) {
                                return null;
                            }
                            return "\n\t" . strtoupper($nc->area->nombre) . ' - ' . strtoupper($nc->categoria->nombre);
                        })
                        ->filter()
                        ->unique()
                        ->values();
                    
                    $concepto .= $nivelesCompetencia->implode('');
                }
                
                $orden->concepto = $concepto;
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
                return response()->json([
                    'mensaje' => 'Orden de pago registrada correctamente.',
                    'orden'   => [
                        'id'                     => $orden->id,
                        'n_orden'                => $orden->n_orden,
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
                    ]
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
        $orden->load(['lista']);

        return [
            'id'                     => $orden->id,
            'n_orden'                 => $orden->n_orden,
            'codigo_lista'           => $orden->lista->codigo_lista,
            'fecha_emision'          => $orden->fecha_emision->format('Y-m-d H:i:s'),
            'precio_unitario'        => number_format($orden->lista->olimpiada->precio_inscripcion, 2),
            'cantidad_inscripciones' => $orden->cantidad_inscripciones,
            'monto'                  => number_format($orden->monto, 2),
            'fecha_pago'            => $orden->fecha_pago ? $orden->fecha_pago->format('Y-m-d H:i:s') : '',
            'estado'                 => $orden->estado,
            'datos_pago'            => [
                'responsable_pago'   => $orden->nombre_responsable,
                'nitci'              => $orden->nitci
            ]
        ];
    }





    public function datosPrevios(string $codigo_lista)
    {
        $lista = Lista::where('codigo_lista', $codigo_lista)->first();

        if (! $lista) {
            return response()->json(['error' => 'Código de lista no encontrado.'], 404);
        }

        $cantidad = $lista->inscripciones()->count();
        $monto = $cantidad * $lista->olimpiada->precio_inscripcion;

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

    public function pagar(PagarOrdenRequest $request)
    {
        try {
            Log::info('Iniciando proceso de pago', ['request_data' => $request->validated()]);

            // Procesar el pago usando el servicio
            $orden = app(OrdenPagoService::class)->procesarPago($request->validated());

            return response()->json([
                'mensaje' => 'Pago registrado correctamente.',
                'orden' => $this->formatOrder($orden)
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al procesar el pago: ' . $e->getMessage(), [
                'stack' => $e->getTraceAsString()
            ]);

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json([
                    'error' => 'La orden de pago o lista especificada no existe.'
                ], 404);
            }

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
