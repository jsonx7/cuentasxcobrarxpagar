<?php

namespace Tests\Feature;

use App\Enums\PayableStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Payable;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PayableManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_are_redirected_to_the_login_screen(): void
    {
        $this->get(route('suppliers.index'))->assertRedirect(route('login'));
        $this->get(route('payables.index'))->assertRedirect(route('login'));
        $this->get(route('payable-payments.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function an_admin_can_register_and_update_a_supplier(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('suppliers.store'), [
            'nombre' => 'Distribuidora Central',
            'nit' => '0615-987654-321-1',
            'contacto' => 'María López',
            'telefono' => '2222-2222',
            'email' => 'ventas@example.com',
            'activo' => '1',
        ]);

        $supplier = Supplier::query()->firstOrFail();

        $response->assertRedirect(route('suppliers.show', $supplier));
        $this->assertSame('0615-987654-321-1', $supplier->nit);
        $this->assertTrue($supplier->activo);

        $this->actingAs($admin)->put(route('suppliers.update', $supplier), [
            'nombre' => 'Distribuidora Actualizada',
            'nit' => '0615-987654-321-1',
            'contacto' => 'Otro Contacto',
            'activo' => '0',
        ])->assertRedirect(route('suppliers.show', $supplier));

        $supplier->refresh();

        $this->assertSame('Distribuidora Actualizada', $supplier->nombre);
        $this->assertFalse($supplier->activo);
    }

    #[Test]
    public function a_supplier_with_payables_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = $this->createSupplier();
        $this->createPayable($supplier);

        $this->actingAs($admin)->delete(route('suppliers.destroy', $supplier))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    #[Test]
    public function an_admin_can_open_the_payable_creation_form_with_the_prima_option(): void
    {
        $admin = User::factory()->admin()->create();
        $this->createSupplier();

        $this->actingAs($admin)->get(route('payables.create'))
            ->assertOk()
            ->assertSee('Total de la deuda')
            ->assertSee('Primera fecha de pago')
            ->assertSee('Sí se abonó prima o anticipo')
            ->assertSee('monto_prima')
            ->assertSee('fecha_prima')
            ->assertDontSee('Número de cuotas');
    }

    #[Test]
    public function an_admin_can_create_a_payable_and_its_code(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = $this->createSupplier();

        $response = $this->actingAs($admin)->post(route('payables.store'), [
            'supplier_id' => $supplier->id,
            'concepto' => 'Factura de materiales de octubre',
            'monto' => 500,
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(15)->toDateString(),
            'observaciones' => 'Pago a 15 días',
        ]);

        $payable = Payable::query()->firstOrFail();

        $response->assertRedirect(route('payables.show', $payable));
        $this->assertSame('CP-'.now()->year.'-0001', $payable->codigo);
        $this->assertSame(PayableStatus::Pendiente, $payable->estado);
        $this->assertEqualsWithDelta(500, (float) $payable->monto, 0.01);
        $this->assertEqualsWithDelta(500, (float) $payable->saldo_pendiente, 0.01);
        $this->assertSame(0, $payable->abonos_registrados);
        $this->assertSame(now()->addDays(15)->toDateString(), $payable->fecha_proxima_pago->toDateString());
        $this->assertDatabaseCount('payable_payments', 0);
    }

    #[Test]
    public function a_custom_code_can_be_supported_when_creating_a_payable(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = $this->createSupplier();

        $this->actingAs($admin)->post(route('payables.store'), [
            'supplier_id' => $supplier->id,
            'codigo' => 'cp-2026-0099',
            'concepto' => 'Factura con código propio',
            'monto' => 500,
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(15)->toDateString(),
        ])->assertRedirect();

        $this->assertSame('CP-2026-0099', Payable::query()->firstOrFail()->codigo);
    }

    #[Test]
    public function a_duplicate_custom_payable_code_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = $this->createSupplier();

        $data = [
            'supplier_id' => $supplier->id,
            'codigo' => 'CP-2026-0100',
            'concepto' => 'Factura con código repetido',
            'monto' => 500,
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(15)->toDateString(),
        ];

        $this->actingAs($admin)->post(route('payables.store'), $data)->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('payables.store'), $data)->assertSessionHasErrors('codigo');

        $this->assertDatabaseCount('payables', 1);
    }

    #[Test]
    public function a_prima_can_be_registered_when_creating_a_payable_and_reverted(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = $this->createSupplier();

        $this->actingAs($admin)->post(route('payables.store'), [
            'supplier_id' => $supplier->id,
            'concepto' => 'Factura con prima',
            'monto' => 500,
            'fecha_emision' => now()->toDateString(),
            'fecha_primer_pago' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
            'tiene_prima' => 1,
            'monto_prima' => 100,
            'fecha_prima' => now()->toDateString(),
        ])->assertRedirect();

        $payable = Payable::query()->firstOrFail();
        $prima = $payable->payments()->firstOrFail();

        $this->assertSame('Prima', $prima->concepto);
        $this->assertTrue($prima->es_prima);
        $this->assertEqualsWithDelta(100, (float) $prima->monto, 0.01);
        $this->assertEqualsWithDelta(400, (float) $payable->fresh()->saldo_pendiente, 0.01);
        $this->assertSame(PayableStatus::Parcial, $payable->fresh()->estado);
        $this->assertSame(0, $payable->fresh()->abonos_registrados);
        $this->assertSame(now()->toDateString(), $payable->fresh()->fecha_proxima_pago->toDateString());

        $this->actingAs($admin)->get(route('payables.show', $payable))
            ->assertOk()
            ->assertSee('Prima');

        $this->actingAs($admin)->delete(route('payable-payments.destroy', $prima))
            ->assertRedirect(route('payables.show', $payable));

        $this->assertEqualsWithDelta(500, (float) $payable->fresh()->saldo_pendiente, 0.01);
        $this->assertSame(PayableStatus::Pendiente, $payable->fresh()->estado);
        $this->assertSame(0, $payable->fresh()->abonos_registrados);
        $this->assertSame(now()->toDateString(), $payable->fresh()->fecha_proxima_pago->toDateString());
    }

    #[Test]
    public function prima_requires_amount_and_date_and_cannot_exceed_the_payable_amount(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = $this->createSupplier();

        $data = [
            'supplier_id' => $supplier->id,
            'concepto' => 'Factura con prima inválida',
            'monto' => 500,
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(15)->toDateString(),
            'tiene_prima' => 1,
        ];

        $this->actingAs($admin)->post(route('payables.store'), $data + [
            'fecha_prima' => now()->toDateString(),
        ])->assertSessionHasErrors('monto_prima');

        $this->actingAs($admin)->post(route('payables.store'), $data + [
            'monto_prima' => 100,
        ])->assertSessionHasErrors('fecha_prima');

        $this->actingAs($admin)->post(route('payables.store'), $data + [
            'monto_prima' => 501,
            'fecha_prima' => now()->toDateString(),
        ])->assertSessionHasErrors('monto_prima');

        $this->assertDatabaseCount('payables', 0);
        $this->assertDatabaseCount('payable_payments', 0);
    }

    #[Test]
    public function the_next_payment_date_advances_one_month_per_regular_payment(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = $this->createSupplier();

        $this->actingAs($admin)->post(route('payables.store'), [
            'supplier_id' => $supplier->id,
            'concepto' => 'Factura por abonos mensuales',
            'monto' => 300,
            'fecha_emision' => '2026-01-01',
            'fecha_primer_pago' => '2026-01-31',
            'fecha_vencimiento' => '2026-01-31',
        ])->assertRedirect();

        $payable = Payable::query()->firstOrFail();

        $this->assertSame('2026-01-31', $payable->fecha_proxima_pago->toDateString());
        $this->assertSame(0, $payable->abonos_registrados);

        $fechasEsperadas = ['2026-02-28', '2026-03-31', null];

        foreach (['2026-01-31', '2026-02-28', '2026-03-31'] as $numero => $fechaPago) {
            $this->actingAs($admin)->post(route('payable-payments.store', $payable), [
                'monto' => 100,
                'concepto' => 'Abono mensual '.($numero + 1),
                'fecha_pago' => $fechaPago,
            ])->assertRedirect();

            $payable->refresh();
            $this->assertSame($numero + 1, $payable->abonos_registrados);
            $this->assertSame($fechasEsperadas[$numero], $payable->fecha_proxima_pago?->toDateString());
        }

        $this->assertSame(PayableStatus::Pagado, $payable->estado);
    }

    #[Test]
    public function payable_payments_store_the_concept_and_move_the_next_payment_date(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = $this->createSupplier();

        $this->actingAs($admin)->post(route('payables.store'), [
            'supplier_id' => $supplier->id,
            'concepto' => 'Factura de materiales',
            'monto' => 600,
            'fecha_emision' => now()->toDateString(),
            'fecha_primer_pago' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
        ])->assertRedirect();

        $payable = Payable::query()->firstOrFail();

        $this->actingAs($admin)->post(route('payable-payments.store', $payable), [
            'monto' => 300,
            'concepto' => 'Primer abono de la factura',
            'fecha_pago' => now()->toDateString(),
        ])->assertRedirect();

        $pago = $payable->payments()->firstOrFail();

        $this->assertSame('Primer abono de la factura', $pago->concepto);
        $this->assertFalse($pago->es_prima);
        $this->assertSame(now()->addMonthNoOverflow()->toDateString(), $payable->fresh()->fecha_proxima_pago->toDateString());

        $this->actingAs($admin)->post(route('payable-payments.store', $payable), [
            'monto' => 300,
            'concepto' => 'Pago final',
            'fecha_pago' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertNull($payable->fresh()->fecha_proxima_pago);
        $this->assertSame(PayableStatus::Pagado, $payable->fresh()->estado);

        $this->actingAs($admin)->get(route('payables.show', $payable))
            ->assertOk()
            ->assertSee('Próxima fecha de pago')
            ->assertSee('Sin pagos pendientes')
            ->assertSee('Primer abono de la factura')
            ->assertSee('Pago final');
    }

    #[Test]
    public function payable_payments_update_the_status_and_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $payable = $this->createPayable($this->createSupplier(), monto: 500);

        $this->actingAs($admin)->post(route('payable-payments.store', $payable), [
            'monto' => 200,
            'concepto' => 'Pago parcial de la factura',
            'fecha_pago' => now()->toDateString(),
            'metodo' => PaymentMethod::Efectivo->value,
            'referencia' => 'PAGO-1',
        ])->assertRedirect(route('payables.show', $payable));

        $this->assertEqualsWithDelta(200, (float) $payable->fresh()->total_pagado, 0.01);
        $this->assertSame(PayableStatus::Parcial, $payable->fresh()->estado);

        $this->actingAs($admin)->post(route('payable-payments.store', $payable), [
            'monto' => 300,
            'concepto' => 'Pago final de la factura',
            'fecha_pago' => now()->toDateString(),
            'metodo' => PaymentMethod::Transferencia->value,
        ])->assertRedirect(route('payables.show', $payable));

        $this->assertEqualsWithDelta(500, (float) $payable->fresh()->total_pagado, 0.01);
        $this->assertSame(PayableStatus::Pagado, $payable->fresh()->estado);
        $this->assertNull($payable->fresh()->fecha_proxima_pago);

        $firstPayment = $payable->payments()->orderBy('id')->firstOrFail();

        $this->actingAs($admin)->delete(route('payable-payments.destroy', $firstPayment))
            ->assertRedirect(route('payables.show', $payable));

        $this->assertEqualsWithDelta(300, (float) $payable->fresh()->total_pagado, 0.01);
        $this->assertSame(PayableStatus::Parcial, $payable->fresh()->estado);
        $this->assertSame(1, $payable->fresh()->abonos_registrados);
        $this->assertSame(
            now()->addDays(15)->addMonthNoOverflow()->toDateString(),
            $payable->fresh()->fecha_proxima_pago->toDateString()
        );
    }

    #[Test]
    public function a_payable_payment_must_be_positive_and_not_exceed_the_balance(): void
    {
        $admin = User::factory()->admin()->create();
        $payable = $this->createPayable($this->createSupplier(), monto: 500);

        $this->actingAs($admin)->post(route('payable-payments.store', $payable), [
            'monto' => 0,
            'concepto' => 'Abono inválido',
            'fecha_pago' => now()->toDateString(),
        ])->assertSessionHasErrors('monto');

        $this->actingAs($admin)->post(route('payable-payments.store', $payable), [
            'monto' => 501,
            'concepto' => 'Abono inválido',
            'fecha_pago' => now()->toDateString(),
        ])->assertSessionHasErrors('monto');

        $this->assertDatabaseCount('payable_payments', 0);
    }

    #[Test]
    public function a_payable_can_be_cancelled_and_deleted_when_it_has_no_payments(): void
    {
        $admin = User::factory()->admin()->create();
        $payable = $this->createPayable($this->createSupplier());

        $this->actingAs($admin)->post(route('payables.cancel', $payable), [
            'motivo' => 'Proveedor canceló la obligación',
        ])->assertRedirect(route('payables.show', $payable));

        $this->assertSame(PayableStatus::Cancelado, $payable->fresh()->estado);
        $this->assertNull($payable->fresh()->fecha_proxima_pago);

        $this->actingAs($admin)->delete(route('payables.destroy', $payable))
            ->assertRedirect(route('payables.index'));

        $this->assertDatabaseMissing('payables', ['id' => $payable->id]);
    }

    #[Test]
    public function the_dashboard_counts_overdue_payment_reminders(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = $this->createSupplier();
        $primerPago = now()->subMonthNoOverflow()->toDateString();

        $this->actingAs($admin)->post(route('payables.store'), [
            'supplier_id' => $supplier->id,
            'concepto' => 'Factura con recordatorio vencido',
            'monto' => 100,
            'fecha_emision' => now()->subMonths(2)->toDateString(),
            'fecha_primer_pago' => $primerPago,
            'fecha_vencimiento' => $primerPago,
        ])->assertRedirect();

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('recordatoriosPayableVencidos', 1);
    }

    #[Test]
    public function a_consultation_user_can_view_payables_but_cannot_manage_them(): void
    {
        $user = User::factory()->create(['role' => UserRole::Consulta]);
        $payable = $this->createPayable($this->createSupplier());

        $this->actingAs($user)->get(route('suppliers.index'))->assertOk();
        $this->actingAs($user)->get(route('payables.index'))
            ->assertOk()
            ->assertSee($payable->codigo);
        $this->actingAs($user)->get(route('payables.show', $payable))->assertOk();
        $this->actingAs($user)->get(route('payable-payments.index'))->assertOk();

        $this->actingAs($user)->get(route('payables.create'))->assertForbidden();
        $this->actingAs($user)->post(route('payables.store'), [])->assertForbidden();
        $this->actingAs($user)->post(route('payable-payments.store', $payable), ['monto' => 1])->assertForbidden();
        $this->actingAs($user)->post(route('payables.cancel', $payable))->assertForbidden();
        $this->actingAs($user)->delete(route('payables.destroy', $payable))->assertForbidden();
    }

    private function createSupplier(): Supplier
    {
        return Supplier::create([
            'nombre' => 'Proveedor de Prueba',
            'nit' => '0615-111111-111-1',
            'activo' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createPayable(Supplier $supplier, ?float $monto = null, array $attributes = []): Payable
    {
        return Payable::create(array_merge([
            'codigo' => 'CP-'.now()->year.'-'.str_pad((string) (Payable::count() + 1), 4, '0', STR_PAD_LEFT),
            'supplier_id' => $supplier->id,
            'concepto' => 'Obligación de prueba',
            'monto' => $monto ?? 500,
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(15)->toDateString(),
            'fecha_primer_pago' => now()->addDays(15)->toDateString(),
            'estado' => PayableStatus::Pendiente,
        ], $attributes));
    }
}
