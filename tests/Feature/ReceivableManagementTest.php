<?php

namespace Tests\Feature;

use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethod;
use App\Enums\ReceivableStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReceivableManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_are_redirected_to_the_login_screen(): void
    {
        $this->get(route('receivables.index'))->assertRedirect(route('login'));
        $this->get(route('receivables.create'))->assertRedirect(route('login'));
    }

    #[Test]
    public function an_admin_can_create_a_receivable_and_its_schedule(): void
    {
        $admin = User::factory()->admin()->create();
        $client = $this->createClient();

        $response = $this->actingAs($admin)->post(route('receivables.store'), [
            'client_id' => $client->id,
            'monto' => 1500,
            'tasa_interes_anual' => 8,
            'tasa_mora_mensual' => 5,
            'plazo' => 30,
            'frecuencia' => PaymentFrequency::Mensual->value,
            'fecha_desembolso' => '2026-01-01',
            'fecha_primer_pago' => '2026-01-08',
            'observaciones' => 'Contrato de prueba',
        ]);

        $receivable = Receivable::query()->firstOrFail();

        $response->assertRedirect(route('receivables.show', $receivable));
        $this->assertSame('CR-'.now()->year.'-0001', $receivable->codigo);
        $this->assertSame(ReceivableStatus::Activo, $receivable->estado);
        $this->assertCount(30, $receivable->installments);
        $this->assertEqualsWithDelta(1500, (float) $receivable->monto, 0.01);
        $this->assertEqualsWithDelta(55.33, (float) $receivable->cuota, 0.01);
        $this->assertEqualsWithDelta(10.00, (float) $receivable->installments()->orderBy('numero')->first()->interes, 0.001);
        $this->assertSame('2026-01-08', $receivable->installments()->orderBy('numero')->first()->fecha_vencimiento->toDateString());
        $this->assertSame('2026-02-08', $receivable->installments()->where('numero', 2)->firstOrFail()->fecha_vencimiento->toDateString());
    }

    #[Test]
    public function an_inactive_client_cannot_be_used_for_a_new_receivable(): void
    {
        $admin = User::factory()->admin()->create();
        $client = $this->createClient(['activo' => false]);

        $this->actingAs($admin)->post(route('receivables.store'), [
            'client_id' => $client->id,
            'monto' => 500,
            'tasa_interes_anual' => 8,
            'plazo' => 10,
            'frecuencia' => PaymentFrequency::Mensual->value,
            'fecha_desembolso' => '2026-01-01',
            'fecha_primer_pago' => '2026-01-08',
        ])->assertSessionHasErrors('client_id');

        $this->assertDatabaseCount('receivables', 0);
    }

    #[Test]
    public function a_receivable_can_be_cancelled(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = $this->createReceivable($this->createClient());

        $this->actingAs($admin)->post(route('receivables.cancel', $receivable), [
            'motivo' => 'Cliente canceló el contrato',
        ])->assertRedirect(route('receivables.show', $receivable));

        $this->assertSame(ReceivableStatus::Cancelado, $receivable->fresh()->estado);
    }

    #[Test]
    public function a_receivable_with_payments_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = $this->createReceivable($this->createClient());
        Payment::create([
            'receivable_id' => $receivable->id,
            'fecha_pago' => now()->toDateString(),
            'monto' => 25,
            'metodo' => PaymentMethod::Efectivo,
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)->delete(route('receivables.destroy', $receivable))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('receivables', ['id' => $receivable->id]);
    }

    #[Test]
    public function a_receivable_without_payments_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = $this->createReceivable($this->createClient());
        Installment::create([
            'receivable_id' => $receivable->id,
            'numero' => 1,
            'fecha_vencimiento' => now()->addDays(7)->toDateString(),
            'monto' => 100,
            'interes' => 0,
            'capital' => 100,
            'saldo_pendiente' => 0,
            'monto_pagado' => 0,
            'mora_pagada' => 0,
        ]);

        $this->actingAs($admin)->delete(route('receivables.destroy', $receivable))
            ->assertRedirect(route('receivables.index'));

        $this->assertDatabaseMissing('receivables', ['id' => $receivable->id]);
        $this->assertDatabaseMissing('installments', ['receivable_id' => $receivable->id]);
    }

    #[Test]
    public function the_receivable_and_report_pdfs_can_be_downloaded(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = $this->createReceivable($this->createClient());

        $this->actingAs($admin)->get(route('receivables.pdf', $receivable))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->actingAs($admin)->get(route('reports.consolidated.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->actingAs($admin)->get(route('reports.overdue.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    #[Test]
    public function a_consultation_user_can_view_receivables_but_cannot_create_them(): void
    {
        $user = User::factory()->create(['role' => UserRole::Consulta]);
        $receivable = $this->createReceivable($this->createClient());

        $this->actingAs($user)->get(route('receivables.index'))
            ->assertOk()
            ->assertSee($receivable->codigo);
        $this->actingAs($user)->get(route('receivables.show', $receivable))->assertOk();
        $this->actingAs($user)->get(route('receivables.pdf', $receivable))->assertOk();

        $this->actingAs($user)->get(route('receivables.create'))->assertForbidden();
        $this->actingAs($user)->post(route('receivables.store'), [])->assertForbidden();
        $this->actingAs($user)->post(route('receivables.cancel', $receivable))->assertForbidden();
        $this->actingAs($user)->delete(route('receivables.destroy', $receivable))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createClient(array $attributes = []): Client
    {
        return Client::create(array_merge([
            'nombre' => 'Cliente Contrato',
            'dui' => '01234567-8',
            'activo' => true,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createReceivable(Client $client, array $attributes = []): Receivable
    {
        return Receivable::create(array_merge([
            'codigo' => 'CR-'.now()->year.'-'.str_pad((string) (Receivable::count() + 1), 4, '0', STR_PAD_LEFT),
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
        ], $attributes));
    }
}
