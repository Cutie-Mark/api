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
            } catch (Exception $inner) {
                DB::rollBack();
                Log::error('Error dentro de la transacción', [
                    'mensaje' => $inner->getMessage(),
                    'stack'   => $inner->getTraceAsString()
                ]);
                throw $inner;
            }
        }
        catch (ModelNotFoundException $e) {
            Log::error('Modelo no encontrado', [
                'model' => $e->getModel(),
                'ids'   => $e->getIds(),
                'msg'   => $e->getMessage()
            ]);
            throw $e; // Se propaga para que la capa superior decida la respuesta
        } catch (QueryException $e) {
            Log::error('Error de base de datos', [
                'sql'      => $e->getSql() ?? 'N/D',
                'bindings' => $e->getBindings() ?? [],
                'code'     => $e->getCode(),
                'msg'      => $e->getMessage()
            ]);
            throw $e;
        } catch (PDOException $e) {
            Log::error('Error de conexión PDO', [
                'code' => $e->getCode(),
                'msg'  => $e->getMessage()
            ]);
            throw $e;
        } catch (Exception $e) {
            Log::error('Error general al procesar el pago', [
                'tipo'  => get_class($e),
                'msg'   => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
