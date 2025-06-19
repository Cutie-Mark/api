<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoriaService
{
    protected $textoService;

    public function __construct(TextoService $textoService)
    {
        $this->textoService = $textoService;
    }

    public function crear(array $datos)
    {
        $upper = mb_strtoupper($datos['nombre'], 'UTF-8');
        $nombreNormalizado = $this->textoService->normalizar($upper);

        $existe = Categoria::get()->contains(fn($categoria) =>
            $this->textoService->normalizar(mb_strtoupper($categoria->nombre, 'UTF-8')) === $nombreNormalizado
        );

        if ($existe) {
            return 'duplicado';
        }

        return Categoria::create([
            'nombre' => $nombreNormalizado,
            'minimo_grado' => $datos['minimo_grado'],
            'maximo_grado' => $datos['maximo_grado'],
        ]);
    }

    public function actualizar(int $id, array $datos)
    {
        $categoria = Categoria::findOrFail($id);
        $categoria->update($datos);
        return $categoria;
    }

    public function cambiarVigencia(int $id, bool $estado)
    {
        $categoria = Categoria::findOrFail($id);
        $categoria->vigente = $estado;
        return $categoria->save();
    }

    public function eliminar(int $id)
    {
        $categoria = Categoria::findOrFail($id);
        if ($categoria->areas()->exists() || $categoria->olimpiadas()->exists()) {
            return 'usada';
        }
        return $categoria->delete();
    }
    
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
