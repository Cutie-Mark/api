<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\Area;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InscripcionQueryService
{
    
    public function getByEstado(string $estado)
    {
        $inscripciones = Inscripcion::with([
                'postulante',
                'nivelCompetencia.area:id,nombre',
                'nivelCompetencia.categoria:id,nombre',
            ])
            ->where('estado', $estado)
            ->get()
            ->groupBy('postulante_id');

        return $inscripciones
            ->map(function ($grupo) {
                $primera = $grupo->first();
                $postulante = $primera->postulante;

                return [
                    'postulante_id' => $postulante->id,
                    'nombres'       => $postulante->nombres,    // <- getNombresAttribute() desencripta
                    'apellidos'     => $postulante->apellidos,  // <- getApellidosAttribute() desencripta
                    'ci'            => $postulante->ci,         // <- getCiAttribute() desencripta
                    'areas'         => $grupo
                                        ->pluck('nivelCompetencia.area.nombre')
                                        ->unique()
                                        ->values(),
                    'categorias'    => $grupo
                                        ->pluck('nivelCompetencia.categoria.nombre')
                                        ->unique()
                                        ->values(),
                    'estado'        => $primera->estado,
                ];
            })
            ->values();
    }

    /**
     * Cambia el estado de una inscripción en particular.
     */
    public function updateEstadoInscripcion(int $id, string $nuevoEstado)
    {
        $inscripcion = Inscripcion::findOrFail($id);
        $inscripcion->estado = $nuevoEstado;
        $inscripcion->save();

        return [
            'id_inscripcion'     => $inscripcion->id,
            'estado_actualizado' => $inscripcion->estado,
        ];
    }

    /**
     * Cuenta cuántos postulantes hay inscritos en un área dada.
     * Se cuentan postulantes distintos (distinct postulante_id).
     */
    public function countByArea(int $areaId)
    {
        $total = Inscripcion::whereHas('nivelCompetencia', function ($q) use ($areaId) {
                $q->where('area_id', $areaId);
            })
            ->distinct('postulante_id')
            ->count('postulante_id');

        $nombre = Area::find($areaId)->nombre ?? 'Área no encontrada';

        return [
            'area_id'      => $areaId,
            'area_nombre'  => $nombre,
            'total'        => $total,
        ];
    }

    /**
     * Cuenta cuántos postulantes hay inscritos en una categoría dada.
     * Se cuentan postulantes distintos (distinct postulante_id).
     */
    public function countByCategoria(int $categoriaId)
    {
        $total = Inscripcion::whereHas('nivelCompetencia', function ($q) use ($categoriaId) {
                $q->where('categoria_id', $categoriaId);
            })
            ->distinct('postulante_id')
            ->count('postulante_id');

        $nombre = Categoria::find($categoriaId)->nombre ?? 'Categoría no encontrada';

        return [
            'categoria_id'      => $categoriaId,
            'categoria_nombre'  => $nombre,
            'total'             => $total,
        ];
    }
}
