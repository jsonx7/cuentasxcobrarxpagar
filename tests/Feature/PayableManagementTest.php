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
    }

    #[Test]
    public function payable_payments_update_the_status_and_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $payable = $this->createPayable($this->createSupplier(), monto: 500);

        $this->actingAs($admin)->post(route('payable-payments.store', $payable), [
            'monto' => 200,
            'fecha_pago' => now()->toDateString(),
            'metodo' => PaymentMethod::Efectivo->value,
            'referencia' => 'PAGO-1',
        ])->assertRedirect(route('payables.show', $payable));

        $this->assertEqualsWithDelta(200, (float) $payable->fresh()->total_pagado, 0.01);
        $this->assertSame(PayableStatus::Parcial, $payable->fresh()->estado);

        $this->actingAs($admin)->post(route('payable-payments.store', $payable), [
            'monto' => 300,
            'fecha_pago' => now()->toDateString(),
            'metodo' => PaymentMethod::Transferencia->value,
        ])->assertRedirect(route('payables.show', $payable));

        $this->assertEqualsWithDelta(500, (float) $payable->fresh()->total_pagado, 0.01);
        $this->assertSame(PayableStatus::Pagado, $payable->fresh()->estado);

        $firstPayment = $payable->payments()->orderBy('id')->firstOrFail();

        $this->actingAs($admin)->delete(route('payable-payments.destroy', $firstPayment))
            ->assertRedirect(route('payables.show', $payable));

        $this->assertEqualsWithDelta(300, (float) $payable->fresh()->total_pagado, 0.01);
        $this->assertSame(PayableStatus::Parcial, $payable->fresh()->estado);
    }

    #[Test]
    public function a_payable_payment_must_be_positive_and_not_exceed_the_balance(): void
    {
        $admin = User::factory()->admin()->create();
        $payable = $this->createPayable($this->createSupplier(), monto: 500);

        $this->actingAs($admin)->post(route('payable-payments.store', $payable), [
            'monto' => 0,
            'fecha_pago' => now()->toDateString(),
        ])->assertSessionHasErrors('monto');

        $this->actingAs($admin)->post(route('payable-payments.store', $payable), [
            'monto' => 501,
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

        $this->actingAs($admin)->delete(route('payables.destroy', $payable))
            ->assertRedirect(route('payables.index'));

        $this->assertDatabaseMissing('payables', ['id' => $payable->id]);
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
            'estado' => PayableStatus::Pendiente,
        ], $attributes));
    }
}
