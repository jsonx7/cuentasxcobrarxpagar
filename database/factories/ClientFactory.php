<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'dui' => fake()->unique()->numerify('########-#'),
            'nit' => fake()->unique()->numerify('########-#'),
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
