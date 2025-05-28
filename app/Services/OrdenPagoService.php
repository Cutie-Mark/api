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
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class OrdenPagoService
{
    private function abort(string $mensaje, int $status = Response::HTTP_UNPROCESSABLE_ENTITY): never
    {
        throw new HttpResponseException(
            response()->json(['error' => $mensaje], $status)
        );
    }

    public function procesarPago(array $data)
    {
        Log::info('Iniciando proceso de pago (Service)', [
            'data' => $data
        ]);
        DB::beginTransaction();

        try {
            $lista = Lista::with('olimpiada')->where('codigo_lista', $data['codigo_lista'])->firstOrFail();
            Log::info('Lista encontrada', ['lista_id' => $lista->id]);

            if (!$lista->olimpiada) {
                Log::error('Inconsistencia de datos: La lista no tiene una olimpiada asociada.', ['lista_id' => $lista->id]);
                $this->abort('Error de configuración: la lista no tiene olimpiada asociada.', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $orden = OrdenPago::where('n_orden', $data['n_orden_pago'])
                ->where('lista_id', $lista->id)
                ->firstOrFail();
            Log::info('Orden encontrada', ['orden_id' => $orden->id]);

            $olimpiada = $lista->olimpiada;
            $fechaPago = Carbon::parse($data['fecha']);
            Log::info('Fecha de pago recibida', [
                'fecha_pago'        => $fechaPago->toDateTimeString(),
                'olimpiada_inicio'  => $olimpiada->fecha_inicio,
                'olimpiada_fin'     => $olimpiada->fecha_fin
            ]);

            if ($fechaPago->lt(Carbon::parse($olimpiada->fecha_inicio)) ||
                $fechaPago->gt(Carbon::parse($olimpiada->fecha_fin))) {
                $this->abort(
                    "La fecha de pago debe estar entre {$olimpiada->fecha_inicio} y {$olimpiada->fecha_fin}."
                );
            }

            if (empty($orden->nombre_responsable)) {
                $this->abort('La orden no tiene un nombre de responsable definido.');
            }

            if (empty($orden->nitci)) {
                $this->abort('La orden no tiene un NIT/CI definido.');
            }

            $orden->estado      = 'pagado';
            $orden->fecha_pago  = $fechaPago;
            $orden->save();
            Log::info('Orden actualizada correctamente', ['orden_id' => $orden->id]);

            $lista->estado = 'Inscripcion Completa';
            $lista->save();
            Log::info('Lista actualizada correctamente', ['lista_id' => $lista->id]);
            $actualizadas = Inscripcion::where('lista_id', $lista->id)
                ->update(['estado' => 'Inscripcion Completa']);
            Log::info('Inscripciones actualizadas', ['cantidad' => $actualizadas]);

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

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::warning('Modelo no encontrado durante procesarPago', [
                'model' => $e->getModel(),
                'ids' => $e->getIds(),
                'original_message' => $e->getMessage()
            ]);
            $this->abort('El número de factura de la orden de pago es incorrecta.', Response::HTTP_NOT_FOUND);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('Error SQL durante procesarPago', [
                'msg' => $e->getMessage(),
                'sql' => $e->getSql() ?? 'No disponible',
                'bindings' => $e->getBindings() ?? [],
                'code' => $e->getCode()
            ]);
            $this->abort('Error en la base de datos al procesar el pago.', Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (PDOException $e) {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Error de PDO durante procesarPago', [
                'msg' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            $this->abort('Error de conexión con la base de datos.', Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Excepción general inesperada en procesarPago: ' . get_class($e) . ' - ' . $e->getMessage(), [
                'stack' => $e->getTraceAsString()
            ]);
            $this->abort(
                env('APP_DEBUG') ? 'Error interno: ' . $e->getMessage() : 'Error interno al procesar el pago.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
