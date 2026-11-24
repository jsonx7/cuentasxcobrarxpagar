<?php

namespace Database\Factories;

use App\Enums\PayableStatus;
use App\Enums\PaymentFrequency;
use App\Models\Payable;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payable>
 */
class PayableFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => 'CP-'.now()->year.'-'.str_pad(
                (string) fake()->unique()->numberBetween(1000, 9999), 4, '0', STR_PAD_LEFT
            ),
            'supplier_id' => Supplier::factory(),
            'concepto' => fake()->sentence(3),
            'monto' => 500,
            'fecha_emision' => '2026-01-01',
            'fecha_vencimiento' => '2026-01-15',
            'frecuencia' => PaymentFrequency::Unico,
            'plazo' => 1,
            'fecha_primer_pago' => '2026-01-15',
            'estado' => PayableStatus::Pendiente,
            'observaciones' => null,
            'created_by' => null,
        ];
    }

    public function partial(): static
    {
        return $this->state(fn () => [
            'estado' => PayableStatus::Parcial,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'estado' => PayableStatus::Pagado,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'estado' => PayableStatus::Cancelado,
        ]);
    }
}
