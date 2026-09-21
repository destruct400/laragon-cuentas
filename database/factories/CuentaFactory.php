<?php

namespace Database\Factories;

use App\Models\Cuenta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cuenta>
 */
class CuentaFactory extends Factory
{
    protected $model = Cuenta::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'nombre_cuenta' => fake()->unique()->words(2, true),
            'saldo' => 0,
        ];
    }
}
