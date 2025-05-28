<?php

namespace App\Services;

use App\Models\Lista;
use App\Models\OrdenPago;
use App\Models\Inscripcion;
use App\Models\Comprobante;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use PDOException;
use Exception;

class OrdenPagoService
{
    public function procesarPago(array $data)
    {
        try {
            Log::info('Iniciando proceso de pago (Service)', [
                'data' => $data
            ]);

            DB::beginTransaction();

            try {
                // 1. Obtener lista y orden
                $lista = Lista::where('codigo_lista', $data['codigo_lista'])->firstOrFail();
                Log::info('Lista encontrada', ['lista_id' => $lista->id]);

                $orden = OrdenPago::where('n_orden', $data['n_orden_pago'])
                    ->where('lista_id', $lista->id)
                    ->firstOrFail();
                Log::info('Orden encontrada', ['orden_id' => $orden->id]);

                // 2. Validar fecha
                $olimpiada = $lista->olimpiada;
                $fechaPago = Carbon::parse($data['fecha']);
                Log::info('Fecha de pago recibida', [
                    'fecha_pago'        => $fechaPago->toDateTimeString(),
                    'olimpiada_inicio'  => $olimpiada->fecha_inicio,
                    'olimpiada_fin'     => $olimpiada->fecha_fin
                ]);

                if ($fechaPago->lt(Carbon::parse($olimpiada->fecha_inicio)) ||
                    $fechaPago->gt(Carbon::parse($olimpiada->fecha_fin))) {
                    throw new Exception(
                        "La fecha de pago debe estar entre {$olimpiada->fecha_inicio} y {$olimpiada->fecha_fin}."
                    );
                }

                // 3. Verificar datos obligatorios en la orden
                if (empty($orden->nombre_responsable)) {
                    throw new Exception('La orden no tiene un nombre de responsable definido.');
                }

                if (empty($orden->nitci)) {
                    throw new Exception('La orden no tiene un NIT/CI definido.');
                }

                // 4. Actualizar orden
                $orden->estado      = 'pagado';
                $orden->fecha_pago  = $fechaPago;
                $orden->save();
                Log::info('Orden actualizada correctamente', ['orden_id' => $orden->id]);

                // 5. Actualizar lista
                $lista->estado = 'Inscripcion Completa';
                $lista->save();
                Log::info('Lista actualizada correctamente', ['lista_id' => $lista->id]);

                // 6. Actualizar inscripciones
                $actualizadas = Inscripcion::where('lista_id', $lista->id)
                    ->update(['estado' => 'Inscripcion Completa']);
                Log::info('Inscripciones actualizadas', ['cantidad' => $actualizadas]);

                // 7. Generar comprobante
                $comprobante = new Comprobante([
                    'orden_pago_id'          => $orden->id,
                    'n_orden'                => $orden->n_orden,
                    'codigo_lista'           => $lista->codigo_lista,
                    'fecha_pago'             => $fechaPago,
                    'precio_unitario'        => $olimpiada->precio_inscripcion,
                    'cantidad_inscripciones' => $orden->cantidad_inscripciones,
                    'monto'                  => $orden->monto,
                    'estado'                 => 'pagado',
                    'responsable_pago'       => $orden->nombre_responsable,
                    'nitci'                  => $orden->nitci,
                ]);
                $comprobante->save();
                Log::info('Comprobante generado', ['comprobante_id' => $comprobante->id]);

                DB::commit();
                Log::info('Transacción completada exitosamente');

                return $orden->fresh();
            } catch (\Exception $innerException) {

                DB::rollBack();
                Log::error('Error dentro de la transacción: ' . $innerException->getMessage(), [
                    'exception_class' => get_class($innerException),
                    'stack' => $innerException->getTraceAsString()
                ]);
                throw $innerException;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('ENTRANDO EN BLOQUE MODELNOTFOUNDEXCEPTION');
            Log::error('Error al procesar el pago - Modelo no encontrado: ' . $e->getMessage(), [
                'model' => $e->getModel(),
                'ids' => $e->getIds()
            ]);
            return response()->json([
                'error' => 'El número de factura de la orden de pago es incorrecta.'
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('ENTRANDO EN BLOQUE QUERYEXCEPTION');
            Log::error('Error de base de datos al procesar el pago: ' . $e->getMessage(), [
                'sql' => $e->getSql() ?? 'No disponible',
                'bindings' => $e->getBindings() ?? [],
                'code' => $e->getCode()
            ]);
            return response()->json([
                'error' => 'Error en la base de datos al procesar el pago.',
                'codigo_error' => $e->getCode(),
                'detalle_tecnico' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        } catch (\PDOException $e) {
            Log::error('ENTRANDO EN BLOQUE PDOEXCEPTION');
            Log::error('Error de PDO al procesar el pago: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'stack' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Error de conexión con la base de datos.',
                'codigo_error' => $e->getCode()
            ], 500);
        } catch (\Exception $e) {
            Log::error('ENTRANDO EN BLOQUE EXCEPTION GENERAL');
            Log::error('Tipo de excepción: ' . get_class($e));
            Log::error('Error al procesar el pago: ' . $e->getMessage(), [
                'stack' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Error interno al procesar el pago.',
                'detalle' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }
}
