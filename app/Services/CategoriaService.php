<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\Categoria;

class CategoriaService
{
    public function getInscripcionesByCategoria(int $categoriaId)
    {
        $inscripciones = Inscripcion::with([
                'postulante.provincia.departamento',
                'nivelCompetencia.area',
                'colegio'
            ])
            ->whereHas('nivelCompetencia', fn($q) => $q->where('categoria_id', $categoriaId))
            ->get()
            ->map(fn($ins) => [
                'id'        => $ins->id,
                'postulante'=> [
                    'nombres'    => $ins->postulante->nombres,
                    'apellidos'  => $ins->postulante->apellidos,
                    'ci'         => $ins->postulante->ci,
                    'departamento'=> $ins->postulante->provincia->departamento->abreviatura,
                    'provincia'   => $ins->postulante->provincia->nombre,
                    'colegio'     => $ins->colegio->nombre,
                    'area'        => $ins->nivelCompetencia->area->nombre,
                    'estado'      => $ins->estado
                ]
            ]);

        return [
            'categoria' => Categoria::find($categoriaId)->nombre,
            'total'     => $inscripciones->count(),
            'data'      => $inscripciones
        ];
    }
}
