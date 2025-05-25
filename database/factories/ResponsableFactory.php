<?php

namespace Database\Factories;

use App\Models\Responsable;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResponsableFactory extends Factory
{
    protected $model = Responsable::class;

    public function definition()
    {
        return [
            'ci' => $this->faker->unique()->numerify('########'),
            'nombre_completo' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'telefono' => $this->faker->numerify('########'),
        ];
    }
}
