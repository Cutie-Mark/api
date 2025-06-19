<?php

namespace App\Services;

use App\Models\Lista;
use App\Models\OrdenPago;
use App\Models\Inscripcion;
use App\Models\Comprobante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use PDOException;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class OrdenPagoService
{
    /**
     * Crea una nueva orden de pago para la lista indicada o actualiza una existente.
     */
    public function crearOrden(array $data): OrdenPago
    {
        try {
            $lista = Lista::where('codigo_lista', $data['codigo_lista'])->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->abort('Código de lista inválido', 404);
        }
        
        $cantidad = $lista->inscripciones()->count();

        if ($cantidad === 0) {
            throw new \InvalidArgumentException('La lista no tiene inscripciones.');
        }

        return DB::transaction(function () use ($data, $lista, $cantidad) {
            // Verificamos si ya existe una orden para esta lista
            $ordenExistente = OrdenPago::where('lista_id', $lista->id)->first();
            
            // Si no existe creamos un número de orden nuevo
            if (!$ordenExistente) {
                $lastOrden = OrdenPago::orderByDesc('id')->first();
                $nextId = $lastOrden ? ((int)$lastOrden->n_orden) + 1 : 1000;
                $n_orden = str_pad((string)$nextId, 7, '0', STR_PAD_LEFT);
            } else {
                // Si existe usamos el mismo número de orden
                $n_orden = $ordenExistente->n_orden;
            }

            $precioUnit = $lista->olimpiada->precio_inscripcion;
            $montoTotal = $cantidad * $precioUnit;
            
            // Usamos updateOrCreate para actualizar o crear según sea necesario
            $orden = OrdenPago::updateOrCreate(
                ['lista_id' => $lista->id],
                [
                    'n_orden' => $n_orden,
                    'monto' => $montoTotal,
                    'cantidad_inscripciones' => $cantidad,
                    'estado' => 'pendiente',
                    'nombre_responsable' => $data['nombre_responsable'],
                    'emitido_por' => $data['emitido_por'],
                    'nitci' => $data['nitci'],
                    'fecha_emision' => Carbon::now(),
                    'unidad' => 'Inscripción',
                    'concepto' => $this->generarConcepto($lista, $cantidad),
                ]
            );

            // Actualizamos las inscripciones solo si no habían sido ya vinculadas
            Inscripcion::where('lista_id', $lista->id)
                ->update([
                    'orden_pago_id' => $orden->id,
                    'estado' => 'Pago Pendiente',
                ]);

            // Actualizamos el estado de la lista
            $lista->update(['estado' => 'Pago Pendiente']);

            return $orden;
        });
    }

    /**
     * Genera el concepto de la orden incluyendo niveles si son ≤5 inscripciones.
     */
    protected function generarConcepto(Lista $lista, int $cantidad): string
    {
        $base = 'Inscripción Olimpiada San Simón acorde a la lista ' . $lista->codigo_lista;

        if ($cantidad > 5) {
            return $base;
        }

        $niveles = $lista->inscripciones()
            ->with(['nivelCompetencia.area', 'nivelCompetencia.categoria'])
            ->get()
            ->map(fn($ins) => $ins->nivelCompetencia
                ? strtoupper($ins->nivelCompetencia->area->nombre)
                  . ' - '
                  . strtoupper($ins->nivelCompetencia->categoria->nombre)
                : null
            )
            ->filter()
            ->unique()
            ->values()
            ->map(fn($txt) => "\n\t{$txt}")
            ->implode('');

        return $base . "\n\nNIVELES DE COMPETENCIA:" . $niveles;
    }

    /**
     * Lanza una respuesta JSON con error y código HTTP.
     *
     * @throws HttpResponseException
     */
    private function abort(string $mensaje, int $status = Response::HTTP_UNPROCESSABLE_ENTITY): never
    {
        // Registramos el error antes de lanzar la excepción para tener mejor trazabilidad
        Log::warning('Operación de pago abortada', [
            'mensaje' => $mensaje,
            'status' => $status,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);
        
        throw new HttpResponseException(
            response()->json([
                'error' => $mensaje
            ], $status)
        );
    }

    /**
     * Procesa el pago de una orden existente.
     */
    public function procesarPago(array $data): OrdenPago
    {
        Log::info('Iniciando proceso de pago (Service)', ['data' => $data]);
        DB::beginTransaction();
        try {
            // Configuramos el modo de error de PHP para capturar todas las advertencias y notificaciones
            $oldErrorLevel = error_reporting(E_ALL);
            // 1. Obtener lista con relaciones necesarias
            $lista = Lista::with(['olimpiada', 'inscripciones.nivelCompetencia.olimpiada'])
                ->where('codigo_lista', $data['codigo_lista'])
                ->first();

            if (!$lista) {
                $this->abort('El código de lista proporcionado no existe en el sistema', 404);
            }

            // 2. Obtener olimpiada directamente de la lista
            $olimpiada = $lista->olimpiada;

            if (!$olimpiada) {
                $this->abort('La lista no está asociada a ninguna olimpiada', 400);
            }
            
            // 3. Validar número de orden
            $orden = OrdenPago::where('n_orden', $data['n_orden_pago'])
                ->where('lista_id', $lista->id)
                ->first();

            if (!$orden) {
                $this->abort('El número de orden de pago es incorrecto o no corresponde a esta lista', 404);
            }

            // 4. Validar fecha de pago contra las fases de inscripción
            $fechaPago = Carbon::parse($data['fecha']);
            
            // Buscar las fases de inscripción en el cronograma
            $fasesInscripcion = $olimpiada->cronogramas()
                ->with('fase')
                ->whereHas('fase', function($query) {
                    $query->where('nombre_fase', 'like', '%inscripci%');
                })
                ->orderBy('fecha_inicio')
                ->get();
            
            if ($fasesInscripcion->count() > 0) {
                // Verificar si la fecha está dentro de alguna fase de inscripción
                $fechaValida = false;
                
                // Obtenemos la primera y última fecha de inscripción para crear un rango completo
                $primeraFase = $fasesInscripcion->first();
                $ultimaFase = $fasesInscripcion->last();
                
                $fechaInicioTotal = Carbon::parse($primeraFase->fecha_inicio);
                $fechaFinTotal = Carbon::parse($ultimaFase->fecha_fin);
                
                // Verificamos si la fecha está en alguna de las fases específicas
                foreach ($fasesInscripcion as $fase) {
                    $fechaInicio = Carbon::parse($fase->fecha_inicio);
                    $fechaFin = Carbon::parse($fase->fecha_fin);
                    
                    if ($fechaPago->between($fechaInicio, $fechaFin)) {
                        $fechaValida = true;
                        break;
                    }
                }
                
                if (!$fechaValida) {
                    // Lanzamos una excepción estándar en lugar de usar abort para garantizar que el mensaje llegue al controlador
                    throw new \Exception(
                        "Las fechas para inscripciones son de {$fechaInicioTotal->format('Y-m-d')} hasta {$fechaFinTotal->format('Y-m-d')}"
                    );
                }
            } else {
                // Si no hay fases de inscripción, usamos las fechas generales de la olimpiada
                $fechaInicio = Carbon::parse($olimpiada->fecha_inicio);
                $fechaFin = Carbon::parse($olimpiada->fecha_fin);
                
                if ($fechaPago->lt($fechaInicio) || $fechaPago->gt($fechaFin)) {
                    // Lanzamos una excepción estándar en lugar de usar abort
                    throw new \Exception(
                        "Las fechas para inscripciones son de {$olimpiada->fecha_inicio} hasta {$olimpiada->fecha_fin}"
                    );
                }
            }

            //  Validaciones de campos obligatorios
            if (empty($orden->nombre_responsable)) {
                $this->abort('La orden no tiene un nombre de responsable definido.');
            }
            if (empty($orden->nitci)) {
                $this->abort('La orden no tiene un NIT/CI definido.');
            }

            //  Marcar como pagado
            $orden->update([
                'estado'     => 'pagado',
                'fecha_pago' => $fechaPago,
            ]);

            // 6. Actualizar lista e inscripciones
            $lista->update(['estado' => 'Inscripcion Completa']);
            Inscripcion::where('lista_id', $lista->id)
                ->update(['estado' => 'Inscripcion Completa']);

            // 7. Generar comprobante
            Comprobante::create([
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

            DB::commit();
            // Restauramos el nivel de error anterior
            error_reporting($oldErrorLevel);
            return $orden->fresh();

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            error_reporting($oldErrorLevel);
            Log::warning('Modelo no encontrado durante procesarPago', [
                'model'            => $e->getModel(),
                'ids'              => $e->getIds(),
                'original_message' => $e->getMessage(),
            ]);
            throw new \Exception('Número de orden de pago incorrecto');

        } catch (QueryException $e) {
            DB::rollBack();
            error_reporting($oldErrorLevel);
            Log::error('Error SQL durante procesarPago', [
                'msg'      => $e->getMessage(),
                'sql'      => $e->getSql() ?? 'No disponible',
                'bindings' => $e->getBindings() ?? [],
                'code'     => $e->getCode(),
            ]);
            throw new \Exception('Error en la base de datos al procesar el pago.');

        } catch (PDOException $e) {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
            error_reporting($oldErrorLevel);
            Log::error('Error PDO durante procesarPago', [
                'msg'  => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new \Exception('Error de conexión con la base de datos.');

        } catch (\Throwable $e) {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
            error_reporting($oldErrorLevel);
            Log::error('Error inesperado en procesarPago: '.$e->getMessage(), [
                'stack' => $e->getTraceAsString(),
            ]);
            // Propagamos la excepción para que el controlador la maneje
            throw $e;
        }
    }
}
