<?php

namespace App\Services;

use App\Models\Area;
use App\Services\TextoService;

class AreaService
{
    protected $textoService;

    public function __construct(TextoService $textoService)
    {
        $this->textoService = $textoService;
    }

    public function crearArea(string $nombre): ?Area
    {
        $upper = mb_strtoupper($nombre, 'UTF-8');
        $nombreNormalizado = $this->textoService->normalizar($upper);

        $existe = Area::get()->contains(fn($area) =>
            $this->textoService->normalizar($area->nombre) === $nombreNormalizado
        );

        if ($existe) {
            return null;
        }

        return Area::create(['nombre' => $nombreNormalizado]);
    }

    public function alternarEstado(int $id, bool $estado): bool
    {
        $area = Area::findOrFail($id);
        $area->vigente = $estado;
        return $area->save();
    }
}
