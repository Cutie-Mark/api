<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\Area;
use App\Models\Categoria;

class InscripcionQueryService
{
    public function obtenerPorEstado(string $estado)
    {
        $inscripciones = Inscripcion::with([
                'postulante:id,nombres,apellidos,ci',
                'nivelCompetencia.area:id,nombre',
                'nivelCompetencia.categoria:id,nombre',
            ])
            ->where('estado', $estado)
            ->get()
            ->groupBy('postulante_id');

        return $inscripciones->map(function ($grupo) {
            $primera = $grupo->first();
            return [
                'postulante_id' => $primera->postulante_id,
                'nombres'       => $primera->postulante->nombres,
                'apellidos'     => $primera->postulante->apellidos,
                'ci'            => $primera->postulante->ci,
                'areas'         => $grupo->pluck('nivelCompetencia.area.nombre')->unique()->values(),
                'categorias'    => $grupo->pluck('nivelCompetencia.categoria.nombre')->unique()->values(),
                'estado'        => $primera->estado,
            ];
        })->values();
    }

    public function actualizarEstadoInscripcion(int $id, string $estado)
    {
        $inscripcion = Inscripcion::findOrFail($id);
        $inscripcion->estado = $estado;
        $inscripcion->save();

        return [
            'id_inscripcion'    => $inscripcion->id,
            'estado_actualizado'=> $inscripcion->estado
        ];
    }

    public function contarPorArea(int $areaId)
    {
        $total = Inscripcion::whereHas('nivelCompetencia', fn($q) => $q->where('area_id', $areaId))
            ->distinct('postulante_id')->count('postulante_id');

        $nombre = Area::find($areaId)->nombre ?? 'Área no encontrada';
        return ['area_id' => $areaId, 'area_nombre' => $nombre, 'total' => $total];
    }

    public function contarPorCategoria(int $categoriaId)
    {
        $total = Inscripcion::whereHas('nivelCompetencia', fn($q) => $q->where('categoria_id', $categoriaId))
            ->distinct('postulante_id')->count('postulante_id');

        $nombre = Categoria::find($categoriaId)->nombre ?? 'Categoría no encontrada';
        return ['categoria_id' => $categoriaId, 'categoria_nombre' => $nombre, 'total' => $total];
    }
}
