<?php

namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    protected $model = Categoria::class;

    public function definition(): array
    {
        return [
            'nombre_categoria' => fake()->unique()->words(2, true),
        ];
    }
}
