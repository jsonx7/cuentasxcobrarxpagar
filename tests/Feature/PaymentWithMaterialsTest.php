<?php

namespace Tests\Feature;

use App\Enums\InstallmentStatus;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethod;
use App\Enums\ReceivableStatus;
use App\Models\Client;
use App\Models\Installment;
use App\Models\Material;
use App\Models\PaymentAllocation;
use App\Models\Receivable;
use App\Models\User;
use App\Services\MoraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentWithMaterialsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_money_payment_is_allocated_to_the_oldest_installments_and_reversed_on_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = $this->receivableWithInstallments(monto: 300, cuotas: 3);

        $this->actingAs($admin)->post(route('payments.store', $receivable), [
            'monto' => 150,
            'fecha_pago' => now()->toDateString(),
            'metodo' => PaymentMethod::Efectivo->value,
            'referencia' => 'REC-001',
        ])->assertRedirect(route('receivables.show', $receivable));

        $this->assertEqualsWithDelta(150, (float) $receivable->fresh()->total_pagado, 0.01);
        $this->assertEqualsWithDelta(100, $this->paidOn($receivable, 1), 0.01);
        $this->assertEqualsWithDelta(50, $this->paidOn($receivable, 2), 0.01);
        $this->assertEqualsWithDelta(0, $this->paidOn($receivable, 3), 0.01);
        $this->assertDatabaseCount('payment_allocations', 2);

        $payment = $receivable->payments()->firstOrFail();
        $this->assertSame(PaymentMethod::Efectivo, $payment->metodo);
        $this->assertSame('REC-001', $payment->referencia);

        $this->actingAs($admin)->delete(route('payments.destroy', $payment))
            ->assertRedirect(route('receivables.show', $receivable));

        $this->assertEqualsWithDelta(0, (float) $receivable->fresh()->total_pagado, 0.01);
        $this->assertDatabaseCount('payment_allocations', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    #[Test]
    public function a_payment_can_be_applied_to_a_specific_installment(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = $this->receivableWithInstallments(monto: 300, cuotas: 3);
        $second = $receivable->installments()->where('numero', 2)->firstOrFail();

        $this->actingAs($admin)->post(route('payments.store', $receivable), [
            'monto' => 50,
            'fecha_pago' => now()->toDateString(),
            'metodo' => PaymentMethod::Efectivo->value,
            'installment_id' => $second->id,
        ])->assertRedirect(route('receivables.show', $receivable));

        $this->assertEqualsWithDelta(0, $this->paidOn($receivable, 1), 0.01);
        $this->assertEqualsWithDelta(50, $this->paidOn($receivable, 2), 0.01);
        $this->assertEqualsWithDelta(0, $this->paidOn($receivable, 3), 0.01);

        $this->assertDatabaseHas('payment_allocations', [
            'installment_id' => $second->id,
            'monto' => 50,
            'tipo' => 'cuota',
        ]);
    }

    #[Test]
    public function a_payment_can_be_made_only_with_materials(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = $this->receivableWithInstallments(monto: 300, cuotas: 3);
        $material = $this->createMaterial(precio: 25);

        $this->actingAs($admin)->post(route('payments.store', $receivable), [
            'monto' => 0,
            'fecha_pago' => now()->toDateString(),
            'materiales' => [
                ['material_id' => $material->id, 'cantidad' => 2, 'precio_unitario' => 25],
            ],
        ])->assertRedirect(route('receivables.show', $receivable));

        $payment = $receivable->payments()->firstOrFail();

        $this->assertSame(PaymentMethod::Material, $payment->metodo);
        $this->assertEqualsWithDelta(50, (float) $payment->monto, 0.01);
        $this->assertEqualsWithDelta(50, (float) $payment->materials()->firstOrFail()->monto, 0.01);
        $this->assertEqualsWithDelta(50, (float) $receivable->fresh()->total_pagado, 0.01);
    }

    #[Test]
    public function a_money_and_materials_payment_is_recorded_as_mixed(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = $this->receivableWithInstallments(monto: 300, cuotas: 3);
        $material = $this->createMaterial(precio: 25);

        $this->actingAs($admin)->post(route('payments.store', $receivable), [
            'monto' => 20,
            'fecha_pago' => now()->toDateString(),
            'metodo' => PaymentMethod::Transferencia->value,
            'materiales' => [
                ['material_id' => $material->id, 'cantidad' => 2, 'precio_unitario' => 25],
            ],
        ])->assertRedirect(route('receivables.show', $receivable));

        $payment = $receivable->payments()->firstOrFail();

        $this->assertSame(PaymentMethod::Mixto, $payment->metodo);
        $this->assertEqualsWithDelta(70, (float) $payment->monto, 0.01);
        $this->assertEqualsWithDelta(70, (float) $receivable->fresh()->total_pagado, 0.01);
        $this->assertDatabaseCount('payment_materials', 1);
    }

    #[Test]
    public function mora_is_paid_separately_and_reverted_on_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = $this->receivableWithInstallments(
            monto: 1000,
            cuotas: 1,
            fechaVencimiento: now()->subDays(30)->toDateString(),
        );

        $this->actingAs($admin)->post(route('payments.store', $receivable), [
            'monto' => 150,
            'monto_mora' => 50,
            'fecha_pago' => now()->toDateString(),
            'metodo' => PaymentMethod::Efectivo->value,
        ])->assertRedirect(route('receivables.show', $receivable));

        $installment = $receivable->installments()->firstOrFail();
        $payment = $receivable->payments()->firstOrFail();

        $this->assertEqualsWithDelta(100, (float) $payment->monto, 0.01);
        $this->assertEqualsWithDelta(50, (float) $payment->monto_mora, 0.01);
        $this->assertEqualsWithDelta(100, (float) $installment->monto_pagado, 0.01);
        $this->assertEqualsWithDelta(50, (float) $installment->mora_pagada, 0.01);
        $this->assertEqualsWithDelta(100, (float) $receivable->fresh()->total_pagado, 0.01);
        $this->assertEqualsWithDelta(50, (float) $receivable->fresh()->total_mora_pagado, 0.01);

        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'installment_id' => $installment->id,
            'monto' => 50,
            'tipo' => 'mora',
        ]);
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'installment_id' => $installment->id,
            'monto' => 100,
            'tipo' => 'cuota',
        ]);

        $this->actingAs($admin)->delete(route('payments.destroy', $payment))
            ->assertRedirect(route('receivables.show', $receivable));

        $this->assertEqualsWithDelta(0, (float) $installment->fresh()->monto_pagado, 0.01);
        $this->assertEqualsWithDelta(0, (float) $installment->fresh()->mora_pagada, 0.01);
        $this->assertDatabaseCount('payment_allocations', 0);
    }

    #[Test]
    public function the_loan_can_be_paid_while_mora_remains_pending_and_is_paid_later(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = $this->receivableWithInstallments(
            monto: 1000,
            cuotas: 1,
            fechaVencimiento: now()->subDays(30)->toDateString(),
        );
        $mora = app(MoraService::class);

        $this->actingAs($admin)->post(route('payments.store', $receivable), [
            'monto' => 1000,
            'fecha_pago' => now()->subDays(10)->toDateString(),
            'metodo' => PaymentMethod::Efectivo->value,
        ])->assertRedirect(route('receivables.show', $receivable));

        $installment = $receivable->installments()->firstOrFail()->fresh();

        $this->assertEqualsWithDelta(0, $installment->saldo, 0.01);
        $this->assertEqualsWithDelta(0, (float) $installment->mora_pagada, 0.01);
        $this->assertEqualsWithDelta(1000, (float) $installment->mora_base, 0.01);
        $this->assertEqualsWithDelta(33.33, $mora->pendiente($installment, now()->subDays(10)), 0.01);
        $this->assertEqualsWithDelta(50, $mora->pendiente($installment, today()), 0.01);
        $this->assertSame(ReceivableStatus::Pagado, $receivable->fresh()->estado);

        $this->actingAs($admin)->post(route('payments.store', $receivable), [
            'monto' => 50,
            'monto_mora' => 50,
            'fecha_pago' => today()->toDateString(),
            'metodo' => PaymentMethod::Efectivo->value,
        ])->assertRedirect(route('receivables.show', $receivable));

        $installment->refresh();
        $moraPayment = $receivable->payments()->latest('id')->firstOrFail();

        $this->assertEqualsWithDelta(50, (float) $installment->mora_pagada, 0.01);
        $this->assertNotNull($installment->mora_fecha_hasta);
        $this->assertEqualsWithDelta(0, $mora->pendiente($installment, today()), 0.01);
        $this->assertEqualsWithDelta(0, $mora->pendiente($installment, today()->addDay()), 0.01);
        $this->assertEqualsWithDelta(0, (float) $receivable->fresh()->saldo_pendiente, 0.01);

        $this->actingAs($admin)->delete(route('payments.destroy', $moraPayment))
            ->assertRedirect(route('receivables.show', $receivable));

        $installment->refresh();

        $this->assertEqualsWithDelta(0, (float) $installment->mora_pagada, 0.01);
        $this->assertNull($installment->mora_fecha_hasta);
        $this->assertEqualsWithDelta(50, $mora->pendiente($installment, today()), 0.01);
    }

    #[Test]
    public function a_partial_mora_payment_keeps_the_base_and_the_remainder_keeps_growing(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = $this->receivableWithInstallments(
            monto: 1000,
            cuotas: 1,
            fechaVencimiento: now()->subDays(30)->toDateString(),
        );
        $mora = app(MoraService::class);

        $this->actingAs($admin)->post(route('payments.store', $receivable), [
            'monto' => 1000,
            'fecha_pago' => today()->toDateString(),
            'metodo' => PaymentMethod::Efectivo->value,
        ])->assertRedirect(route('receivables.show', $receivable));

        $installment = $receivable->installments()->firstOrFail()->fresh();

        $this->assertEqualsWithDelta(1000, (float) $installment->mora_base, 0.01);
        $this->assertEqualsWithDelta(50, $mora->pendiente($installment, today()), 0.01);

        $this->actingAs($admin)->post(route('payments.store', $receivable), [
            'monto' => 20,
            'monto_mora' => 20,
            'fecha_pago' => today()->toDateString(),
            'metodo' => PaymentMethod::Efectivo->value,
        ])->assertRedirect(route('receivables.show', $receivable));

        $installment->refresh();

        $this->assertEqualsWithDelta(20, (float) $installment->mora_pagada, 0.01);
        $this->assertNull($installment->mora_fecha_hasta);
        $this->assertEqualsWithDelta(30, $mora->pendiente($installment, today()), 0.01);
        $this->assertEqualsWithDelta(46.67, $mora->pendiente($installment, today()->addDays(10)), 0.01);
    }

    #[Test]
    public function pending_mora_of_a_paid_loan_still_appears_in_reports(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = $this->receivableWithInstallments(
            monto: 1000,
            cuotas: 1,
            fechaVencimiento: now()->subDays(30)->toDateString(),
        );

        $this->actingAs($admin)->post(route('payments.store', $receivable), [
            'monto' => 1000,
            'fecha_pago' => today()->toDateString(),
            'metodo' => PaymentMethod::Efectivo->value,
        ])->assertRedirect(route('receivables.show', $receivable));

        $this->actingAs($admin)->get(route('reports.overdue'))
            ->assertOk()
            ->assertViewHas('totales', fn (array $totales) => (float) $totales['mora'] >= 49.99
                && (int) $totales['cantidad'] === 1);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('moraPendiente', fn ($total) => (float) $total >= 49.99);
    }

    #[Test]
    public function paying_an_installment_settles_it_and_moves_to_the_next_even_with_pending_mora(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = $this->receivableWithInstallments(
            monto: 200,
            cuotas: 2,
            fechaVencimiento: now()->subDays(30)->toDateString(),
        );
        $mora = app(MoraService::class);
        [$primera, $segunda] = $receivable->installments()->orderBy('numero')->get()->all();

        $this->actingAs($admin)->get(route('receivables.show', $receivable))
            ->assertOk()
            ->assertSee('show({ id: '.$primera->id.', monto: '.$primera->saldo.' })', false)
            ->assertDontSee('show({ id: '.$primera->id.', monto: '.$primera->saldo.', mora:', false);

        $this->actingAs($admin)->post(route('payments.store', $receivable), [
            'monto' => 100,
            'installment_id' => $primera->id,
            'fecha_pago' => today()->toDateString(),
            'metodo' => PaymentMethod::Efectivo->value,
        ])->assertRedirect(route('receivables.show', $receivable));

        $primera->refresh();
        $segunda->refresh();

        $this->assertSame(InstallmentStatus::Pagada, $primera->estado);
        $this->assertEqualsWithDelta(0, $primera->saldo, 0.01);
        $this->assertEqualsWithDelta(0, (float) $primera->mora_pagada, 0.01);
        $this->assertGreaterThan(0, $mora->pendiente($primera, today()));
        $this->assertSame(InstallmentStatus::Vencida, $segunda->estado);

        $this->actingAs($admin)->post(route('payments.store', $receivable), [
            'monto' => 100,
            'fecha_pago' => today()->toDateString(),
            'metodo' => PaymentMethod::Efectivo->value,
        ])->assertRedirect(route('receivables.show', $receivable));

        $segunda->refresh();

        $this->assertSame(InstallmentStatus::Pagada, $segunda->estado);
        $this->assertEqualsWithDelta(0, $segunda->saldo, 0.01);
        $this->assertEqualsWithDelta(0, (float) $segunda->mora_pagada, 0.01);
        $this->assertTrue(PaymentAllocation::query()
            ->where('tipo', 'cuota')
            ->whereHas('payment', fn ($query) => $query->where('receivable_id', $receivable->id))
            ->where('installment_id', $segunda->id)
            ->exists());
    }

    #[Test]
    public function a_payment_cannot_exceed_the_receivable_balance(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = $this->receivableWithInstallments(monto: 300, cuotas: 3);

        $this->actingAs($admin)->post(route('payments.store', $receivable), [
            'monto' => 301,
            'fecha_pago' => now()->toDateString(),
        ])->assertSessionHasErrors('monto');

        $this->assertDatabaseCount('payments', 0);
    }

    #[Test]
    public function a_mora_payment_cannot_exceed_the_pending_mora(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = $this->receivableWithInstallments(
            monto: 1000,
            cuotas: 1,
            fechaVencimiento: now()->subDays(30)->toDateString(),
        );

        $this->actingAs($admin)->post(route('payments.store', $receivable), [
            'monto' => 100,
            'monto_mora' => 51,
            'fecha_pago' => now()->toDateString(),
        ])->assertSessionHasErrors('monto_mora');

        $this->assertDatabaseCount('payments', 0);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createMaterial(float $precio = 25): Material
    {
        return Material::create([
            'nombre' => 'Material de Prueba',
            'codigo' => 'MAT-'.Material::count().'-'.random_int(1, 999),
            'unidad' => 'unidad',
            'precio' => $precio,
            'activo' => true,
        ]);
    }

    private function receivableWithInstallments(
        float $monto = 300,
        int $cuotas = 3,
        ?string $fechaVencimiento = null,
    ): Receivable {
        $client = Client::create([
            'nombre' => 'Cliente Abonos',
            'activo' => true,
        ]);

        $receivable = Receivable::create([
            'codigo' => 'CR-'.now()->year.'-'.str_pad((string) (Receivable::count() + 1), 4, '0', STR_PAD_LEFT),
            'client_id' => $client->id,
            'monto' => $monto,
            'tasa_interes_anual' => 0,
            'plazo' => $cuotas,
            'frecuencia' => PaymentFrequency::Semanal,
            'fecha_desembolso' => now()->subDays(60)->toDateString(),
            'fecha_primer_pago' => $fechaVencimiento ?? now()->addDays(7)->toDateString(),
            'cuota' => round($monto / $cuotas, 2),
            'interes_total' => 0,
            'total_a_pagar' => $monto,
            'tasa_mora_mensual' => 5,
            'estado' => ReceivableStatus::Activo,
        ]);

        $montoCuota = round($monto / $cuotas, 2);
        $fecha = $fechaVencimiento ?? now()->addDays(7)->toDateString();

        for ($numero = 1; $numero <= $cuotas; $numero++) {
            Installment::create([
                'receivable_id' => $receivable->id,
                'numero' => $numero,
                'fecha_vencimiento' => $fecha,
                'monto' => $montoCuota,
                'interes' => 0,
                'capital' => $montoCuota,
                'saldo_pendiente' => $montoCuota,
                'abono_extra' => 0,
                'monto_pagado' => 0,
                'mora_pagada' => 0,
            ]);
        }

        return $receivable;
    }

    private function paidOn(Receivable $receivable, int $numero): float
    {
        return (float) PaymentAllocation::query()
            ->join('installments', 'payment_allocations.installment_id', '=', 'installments.id')
            ->where('installments.receivable_id', $receivable->id)
            ->where('installments.numero', $numero)
            ->where('payment_allocations.tipo', 'cuota')
            ->sum('payment_allocations.monto');
    }
}
