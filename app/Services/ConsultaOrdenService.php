<?php

namespace App\Services;

use App\Models\Lista;
use App\Models\OrdenPago;
use Illuminate\Http\JsonResponse;

class ConsultaOrdenService
{
    /**
     * Obtener una orden de pago por código de lista
     */
    public function obtenerPorCodigoLista(string $codigo_lista)
    {
        // Buscar la lista por su código
        $lista = Lista::where('codigo_lista', $codigo_lista)->first();
        if (!$lista) {
            return ['error' => 'Código de lista no encontrado.', 'code' => 404];
        }

        // Buscar la orden de pago asociada a la lista
        $orden = OrdenPago::where('lista_id', $lista->id)->orderByDesc('created_at')->first();
        if (!$orden) {
            return ['error' => 'No existe orden de pago para la lista dada.', 'code' => 404];
        }

        // Cargar relaciones necesarias
        $orden->load([
            'lista.olimpiada',
            'lista.inscripciones.nivelCompetencia.area',
            'lista.inscripciones.nivelCompetencia.categoria'
        ]);

        return $this->formatearDetalleOrden($orden);
    }

    /**
     * Obtener una orden de pago por número de orden
     */
    public function obtenerPorNumeroOrden(string $n_orden)
    {
        // Buscar la orden por su número
        $orden = OrdenPago::where('n_orden', $n_orden)
            ->with([
                'lista.olimpiada',
                'lista.inscripciones.nivelCompetencia.area',
                'lista.inscripciones.nivelCompetencia.categoria'
            ])
            ->first();

        if (!$orden) {
            return ['error' => 'Número de orden no encontrado.', 'code' => 404];
        }

        return $this->formatearDetalleOrden($orden);
    }

    /**
     * Formatear los detalles de una orden de pago
     */
    protected function formatearDetalleOrden(OrdenPago $orden): array
    {
        // 1. Precio unitario (como string con 2 decimales)
        $precioUnitario = number_format($orden->lista->olimpiada->precio_inscripcion, 2);

        // 2. Monto total (como string con 2 decimales)
        $monto = number_format($orden->monto, 2);

        // 3. Cantidad de inscripciones
        $cantidad = $orden->cantidad_inscripciones;

        // 4. Construir niveles_competencia solo si hay ≤ 5 inscripciones
        $nivelesStr = '';
        if ($cantidad <= 5) {
            $nivelesCollection = $orden->lista
                ->inscripciones
                ->map(function ($ins) {
                    $nc = $ins->nivelCompetencia;
                    if (!$nc || !$nc->area || !$nc->categoria) {
                        return null;
                    }
                    return strtoupper($nc->area->nombre) . ' - ' . strtoupper($nc->categoria->nombre);
                })
                ->filter()    // descartar nulls
                ->unique()    // quitar duplicados
                ->values();

            $nivelesStr = $nivelesCollection->implode(', ');
        }

        // 5. Retornar datos formateados
        return [
            'id' => $orden->id,
            'n_orden' => $orden->n_orden,
            'codigo_lista' => $orden->lista->codigo_lista,
            'fecha_emision' => $orden->fecha_emision->format('Y-m-d H:i:s'),
            'precio_unitario' => $precioUnitario,
            'monto' => $monto,
            'estado' => $orden->estado,
            'cantidad_inscripciones' => $cantidad,
            'nombre_responsable' => $orden->nombre_responsable,
            'emitido_por' => $orden->emitido_por,
            'nitci' => $orden->nitci,
            'unidad' => $orden->unidad,
            'concepto' => $orden->concepto,
            'niveles_competencia' => $nivelesStr,
        ];
    }
}
