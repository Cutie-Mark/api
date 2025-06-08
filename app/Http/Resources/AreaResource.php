<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request)
    {
        return [
            'id'      => $this->id,
            'nombre'  => $this->nombre,
            'vigente' => $this->vigente,

        ];
    }
}
