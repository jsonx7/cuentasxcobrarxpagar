<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->company(),
            'nit' => fake()->unique()->numerify('########-#'),
            'contacto' => fake()->name(),
            'telefono' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'direccion' => Str::limit(fake()->address(), 255, ''),
            'notas' => null,
            'activo' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'activo' => false,
        ]);
    }
}
