<?php

namespace Database\Factories;

use App\Enums\PaymentFrequency;
use App\Enums\ReceivableStatus;
use App\Models\Client;
use App\Models\Receivable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receivable>
 */
class ReceivableFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => 'CR-'.now()->year.'-'.str_pad(
                (string) fake()->unique()->numberBetween(1000, 9999), 4, '0', STR_PAD_LEFT
            ),
            'client_id' => Client::factory(),
            'monto' => 1000,
            'tasa_interes_anual' => 8,
            'plazo' => 10,
            'frecuencia' => PaymentFrequency::Mensual,
            'fecha_desembolso' => '2026-01-01',
            'fecha_primer_pago' => '2026-02-01',
            'cuota' => 100,
            'interes_total' => 0,
            'total_a_pagar' => 1000,
            'tasa_mora_mensual' => 5,
            'estado' => ReceivableStatus::Activo,
            'observaciones' => null,
            'created_by' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'estado' => ReceivableStatus::Pagado,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'estado' => ReceivableStatus::Cancelado,
        ]);
    }
}
