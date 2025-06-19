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
use App\Services\ConsultaOrdenService;
use App\Http\Requests\CrearOrdenPagoRequest;
use Illuminate\Http\Exceptions\HttpResponseException;


class OrdenPagoController extends Controller
{
    protected $service;
    protected $consultaService;

    public function __construct(OrdenPagoService $service, ConsultaOrdenService $consultaService)
    {
        $this->service = $service;
        $this->consultaService = $consultaService;
    }

    public function listar()
    {
        $orders = OrdenPago::with('lista.olimpiada')->get()
            ->map(fn($order) => $this->formatoOrden($order));
        return response()->json($orders, 200);
    }

    public function mostrarPorCodigoLista(string $codigo_lista)
    {
        $resultado = $this->consultaService->obtenerPorCodigoLista($codigo_lista);
        
        // Verificar si hubo un error
        if (isset($resultado['error'])) {
            return response()->json(['error' => $resultado['error']], $resultado['code']);
        }
        
        // Si no hay error, retornar el resultado formateado
        return response()->json($resultado, 200);
    }

    public function mostrarPorNumeroOrden(string $n_orden)
    {
        $resultado = $this->consultaService->obtenerPorNumeroOrden($n_orden);
        
        // Verificar si hubo un error
        if (isset($resultado['error'])) {
            return response()->json(['error' => $resultado['error']], $resultado['code']);
        }
        
        // Si no hay error, retornar el resultado formateado
        return response()->json($resultado, 200);
    }

    public function crear(CrearOrdenPagoRequest $request)
    {
        try {
            $orden = $this->service->crearOrden($request->validated());
            return response()->json([
                'mensaje' => 'Orden de pago registrada correctamente.',
                'orden'   => $this->formatoOrden($orden),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            // Aquí interceptamos la excepción por lista vacía
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si no existe la lista
            return response()->json([
                'error' => 'Código de lista inválido'
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Error interno en crear orden: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $request->validated()
            ]);
            return response()->json([
                'error' => 'Error interno al crear orden de pago'
            ], 500);
        }
    }


    protected function formatoOrden(OrdenPago $orden): array
    {
        $orden->loadMissing('lista.olimpiada');

        return [
            'id'                     => $orden->id,
            'n_orden'                 => $orden->n_orden,
            'codigo_lista'           => $orden->lista->codigo_lista ?? null,
            'fecha_emision'          => $orden->fecha_emision->format('Y-m-d H:i:s'),
            'precio_unitario'        => $orden->lista && $orden->lista->olimpiada ? number_format($orden->lista->olimpiada->precio_inscripcion, 2) : null,
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

    public function pagar(PagarOrdenRequest $request, OrdenPagoService $service)
    {
        try {
            // El servicio devolverá una excepción si hay errores, que se capturarán a continuación
            $orden = $service->procesarPago($request->validated());
            
            return response()->json([
                'mensaje' => 'Pago registrado correctamente.',
                'orden'   => $this->formatoOrden($orden),
            ], 200);
            
        } catch (HttpResponseException $e) {
            // Las excepciones lanzadas por abort() en el servicio
            return $e->getResponse();
            
        } catch (\InvalidArgumentException $e) {
            // Para errores de validación específicos
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si no se encuentra un modelo específico
            if (str_contains($e->getModel(), 'Lista')) {
                return response()->json([
                    'error' => 'Código de lista inválido'
                ], 404);
            } elseif (str_contains($e->getModel(), 'OrdenPago')) {
                return response()->json([
                    'error' => 'Número de orden incorrecto'
                ], 404);
            } else {
                $model = basename(str_replace('\\', '/', $e->getModel()));
                return response()->json([
                    'error' => "No se encontró el recurso solicitado: {$model}"
                ], 404);
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Para errores de validación de Laravel
            return response()->json([
                'error' => $e->errors()[array_key_first($e->errors())][0]
            ], 422);
            
        } catch (\Exception $e) {
            // Para otros errores específicos con mensajes amigables
            Log::error('Error en procesamiento de pago: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);
            
            // Si es un error de fechas o específico, intentamos extraerlo
            if (str_contains(strtolower($e->getMessage()), 'fecha') || 
                str_contains($e->getMessage(), 'inscripciones son de')) {
                return response()->json([
                    'error' => $e->getMessage()
                ], 400);
            }
            
            // Reportamos el mensaje real del error en lugar de un mensaje genérico
            return response()->json([
                'error' => !empty($e->getMessage()) ? $e->getMessage() : 'Se produjo un error al procesar el pago. Por favor, verifique los datos.'
            ], 500);
        }
    }

}
