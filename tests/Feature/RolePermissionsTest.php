<?php

namespace Tests\Feature;

use App\Enums\PayableStatus;
use App\Enums\PaymentFrequency;
use App\Enums\ReceivableStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Material;
use App\Models\Payable;
use App\Models\Receivable;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_operator_can_manage_clients_materials_suppliers_receivables_and_payables(): void
    {
        $operator = User::factory()->operador()->create();

        $this->actingAs($operator)->post(route('clients.store'), [
            'nombre' => 'Cliente del Operador',
            'dui' => '01234567-8',
            'activo' => '1',
        ])->assertRedirect();

        $client = Client::query()->firstOrFail();

        $this->actingAs($operator)->post(route('materials.store'), [
            'nombre' => 'Material del Operador',
            'precio' => 10,
            'activo' => '1',
        ])->assertRedirect();

        $this->actingAs($operator)->post(route('suppliers.store'), [
            'nombre' => 'Proveedor del Operador',
            'nit' => '0615-222222-222-2',
            'activo' => '1',
        ])->assertRedirect();

        $supplier = Supplier::query()->firstOrFail();

        $this->actingAs($operator)->post(route('receivables.store'), [
            'client_id' => $client->id,
            'monto' => 500,
            'tasa_interes_anual' => 8,
            'plazo' => 10,
            'frecuencia' => PaymentFrequency::Mensual->value,
            'fecha_desembolso' => '2026-01-01',
            'fecha_primer_pago' => '2026-01-08',
        ])->assertRedirect();

        $receivable = Receivable::query()->firstOrFail();

        $this->assertCount(10, $receivable->installments);

        $this->actingAs($operator)->post(route('payments.store', $receivable), [
            'monto' => 50,
            'fecha_pago' => now()->toDateString(),
        ])->assertRedirect(route('receivables.show', $receivable));

        $this->assertDatabaseCount('payments', 1);
        $this->assertEqualsWithDelta(50, (float) $receivable->fresh()->total_pagado, 0.01);

        $this->actingAs($operator)->post(route('payables.store'), [
            'supplier_id' => $supplier->id,
            'concepto' => 'Factura del operador',
            'monto' => 300,
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(15)->toDateString(),
        ])->assertRedirect();

        $payable = Payable::query()->firstOrFail();

        $this->actingAs($operator)->post(route('payable-payments.store', $payable), [
            'monto' => 100,
            'fecha_pago' => now()->toDateString(),
        ])->assertRedirect(route('payables.show', $payable));

        $this->assertEqualsWithDelta(100, (float) $payable->fresh()->total_pagado, 0.01);
        $this->assertSame(PayableStatus::Parcial, $payable->fresh()->estado);
    }

    #[Test]
    public function an_operator_cannot_manage_users(): void
    {
        $operator = User::factory()->operador()->create();

        $this->actingAs($operator)->get(route('users.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('users.create'))->assertForbidden();
        $this->actingAs($operator)->post(route('users.store'), [])->assertForbidden();

        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function an_operator_sees_operational_actions_but_not_the_users_section(): void
    {
        $operator = User::factory()->operador()->create();

        $this->actingAs($operator)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('clients.index'), false)
            ->assertSee(route('receivables.create'), false)
            ->assertDontSee(route('users.index'), false);
    }

    #[Test]
    public function an_admin_can_still_manage_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('users.create'))->assertOk();
    }

    #[Test]
    public function the_operator_role_is_available_when_creating_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('users.create'))
            ->assertOk()
            ->assertSee('value="'.UserRole::Operador->value.'"', false)
            ->assertSee('Operador');
    }

    #[Test]
    public function a_consultation_user_can_read_every_module_but_cannot_write(): void
    {
        $user = User::factory()->create(['role' => UserRole::Consulta]);
        $client = Client::create([
            'nombre' => 'Cliente Consulta',
            'activo' => true,
        ]);
        $material = Material::create([
            'nombre' => 'Material Consulta',
            'precio' => 10,
            'activo' => true,
        ]);
        $supplier = Supplier::create([
            'nombre' => 'Proveedor Consulta',
            'activo' => true,
        ]);
        $receivable = $this->createReceivable($client);
        $payable = Payable::create([
            'codigo' => 'CP-'.now()->year.'-0001',
            'supplier_id' => $supplier->id,
            'concepto' => 'Obligación de consulta',
            'monto' => 100,
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(15)->toDateString(),
            'estado' => PayableStatus::Pendiente,
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('clients.index'))->assertOk();
        $this->actingAs($user)->get(route('clients.show', $client))->assertOk();
        $this->actingAs($user)->get(route('materials.index'))->assertOk();
        $this->actingAs($user)->get(route('materials.show', $material))->assertOk();
        $this->actingAs($user)->get(route('suppliers.index'))->assertOk();
        $this->actingAs($user)->get(route('suppliers.show', $supplier))->assertOk();
        $this->actingAs($user)->get(route('receivables.index'))->assertOk();
        $this->actingAs($user)->get(route('receivables.show', $receivable))->assertOk();
        $this->actingAs($user)->get(route('payments.index'))->assertOk();
        $this->actingAs($user)->get(route('payables.index'))->assertOk();
        $this->actingAs($user)->get(route('payables.show', $payable))->assertOk();
        $this->actingAs($user)->get(route('payable-payments.index'))->assertOk();
        $this->actingAs($user)->get(route('reports.index'))->assertOk();
        $this->actingAs($user)->get(route('reports.consolidated'))->assertOk();
        $this->actingAs($user)->get(route('reports.overdue'))->assertOk();

        $this->actingAs($user)->get(route('clients.create'))->assertForbidden();
        $this->actingAs($user)->get(route('materials.create'))->assertForbidden();
        $this->actingAs($user)->get(route('suppliers.create'))->assertForbidden();
        $this->actingAs($user)->get(route('receivables.create'))->assertForbidden();
        $this->actingAs($user)->get(route('payables.create'))->assertForbidden();

        $this->actingAs($user)->post(route('payments.store', $receivable), ['monto' => 1])->assertForbidden();
        $this->actingAs($user)->post(route('payable-payments.store', $payable), ['monto' => 1])->assertForbidden();
        $this->actingAs($user)->post(route('receivables.cancel', $receivable))->assertForbidden();
        $this->actingAs($user)->post(route('payables.cancel', $payable))->assertForbidden();
    }

    private function createReceivable(Client $client): Receivable
    {
        return Receivable::create([
            'codigo' => 'CR-'.now()->year.'-0001',
            'client_id' => $client->id,
            'monto' => 100,
            'tasa_interes_anual' => 0,
            'plazo' => 1,
            'frecuencia' => PaymentFrequency::Unico,
            'fecha_desembolso' => now()->toDateString(),
            'fecha_primer_pago' => now()->addDays(7)->toDateString(),
            'cuota' => 100,
            'interes_total' => 0,
            'total_a_pagar' => 100,
            'tasa_mora_mensual' => 5,
            'estado' => ReceivableStatus::Activo,
        ]);
    }
}
