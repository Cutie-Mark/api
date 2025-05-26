<?php

namespace Database\Factories;

use App\Models\Lista;
use App\Models\Responsable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ListaFactory extends Factory
{
    protected $model = Lista::class;

    public function definition()
    {
        $olimpiada = \App\Models\Olimpiada::factory()->create();

        return [
            'codigo_lista' => Str::upper(Str::random(6)),
            'olimpiada_id' => $olimpiada->id,
            'estado'       => 'Preinscrito',
            'responsable_id' => Responsable::factory(),
        ];
    }
}
