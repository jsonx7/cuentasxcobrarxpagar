<?php

namespace Database\Factories;

use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(2, true),
            'codigo' => fake()->unique()->bothify('MAT-####'),
            'unidad' => 'unidad',
            'precio' => fake()->randomFloat(2, 1, 500),
            'activo' => true,
            'notas' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'activo' => false,
        ]);
    }
}
