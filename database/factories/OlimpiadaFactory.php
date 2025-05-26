<?php

namespace Database\Factories;

use App\Models\Olimpiada;
use Illuminate\Database\Eloquent\Factories\Factory;

class OlimpiadaFactory extends Factory
{
    protected $model = Olimpiada::class;

    public function definition()
    {
        return [
            'nombre' => $this->faker->word,
            'gestion' => $this->faker->year,
            'fecha_inicio' => $this->faker->date,
            'fecha_fin' => $this->faker->date,
            'vigente' => true,
        ];
    }
}
