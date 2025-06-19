<?php

namespace App\Services;

use App\Models\Cronograma;
use App\Models\Olimpiada;
use Carbon\Carbon;

class CronogramaService
{
    public function actualizarFases(int $olimpiadaId, array $agregar, array $borrar)
    {
        $agregados = [];
        $borrados = [];

        foreach ($agregar as $idFase) {
            $cronograma = Cronograma::firstOrCreate(
                ['olimpiada_id' => $olimpiadaId, 'id_fase' => $idFase],
                ['fecha_inicio' => null, 'fecha_fin' => null]
            );

            if ($cronograma->wasRecentlyCreated) {
                $agregados[] = $cronograma;
            }
        }

        foreach ($borrar as $idFase) {
            $cronograma = Cronograma::where('olimpiada_id', $olimpiadaId)
                                    ->where('id_fase', $idFase)
                                    ->first();
            if ($cronograma) {
                $cronograma->delete();
                $borrados[] = ['id' => $cronograma->id, 'id_fase' => $idFase];
            }
        }

        return compact('agregados', 'borrados');
    }

    public function actualizarFechas(array $datos)
    {
        $actualizados = [];

        foreach ($datos as $cronogramaInput) {
            $cronograma = Cronograma::findOrFail($cronogramaInput['id']);
            $olimpiada = $cronograma->olimpiada;

            $fechaInicio = Carbon::parse($cronogramaInput['fecha_inicio']);
            $fechaFin = Carbon::parse($cronogramaInput['fecha_fin']);

            if ($fechaInicio->lt(Carbon::parse($olimpiada->fecha_inicio)) || $fechaFin->gt(Carbon::parse($olimpiada->fecha_fin))) {
                return "Las fechas del cronograma ID {$cronograma->id} deben estar dentro del periodo de la olimpiada.";
            }

            $cronograma->update([
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ]);

            $actualizados[] = $cronograma;
        }

        return $actualizados;
    }



}