<?php

namespace Tests\Feature;

use App\Enums\PaymentFrequency;
use App\Enums\ReceivableStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_are_redirected_to_the_login_screen(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('clients.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function an_admin_can_register_a_client_and_the_dui_is_normalized(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('clients.store'), [
            'nombre' => 'Juan Carlos Pérez',
            'dui' => '012345678',
            'nit' => '0615-123456-123-4',
            'telefono' => '7000-0000',
            'email' => 'juan@example.com',
            'direccion' => 'San Salvador',
            'activo' => '1',
        ]);

        $client = Client::query()->firstOrFail();

        $response->assertRedirect(route('clients.show', $client));
        $this->assertSame('01234567-8', $client->dui);
        $this->assertTrue($client->activo);
        $this->assertSame('Juan Carlos Pérez', $client->nombre);
    }

    #[Test]
    public function a_client_can_be_updated_and_deactivated(): void
    {
        $admin = User::factory()->admin()->create();
        $client = $this->createClient(['nombre' => 'Cliente Original', 'activo' => true]);

        $this->actingAs($admin)->put(route('clients.update', $client), [
            'nombre' => 'Cliente Actualizado',
            'dui' => '01234567-8',
            'telefono' => '7777-7777',
            'activo' => '0',
        ])->assertRedirect(route('clients.show', $client));

        $client->refresh();

        $this->assertSame('Cliente Actualizado', $client->nombre);
        $this->assertFalse($client->activo);
    }

    #[Test]
    public function the_dui_and_nit_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        $this->createClient(['dui' => '01234567-8', 'nit' => '0615-123456-123-4']);

        $this->actingAs($admin)->post(route('clients.store'), [
            'nombre' => 'Otro Cliente',
            'dui' => '01234567-8',
        ])->assertSessionHasErrors('dui');

        $this->actingAs($admin)->post(route('clients.store'), [
            'nombre' => 'Otro Cliente',
            'nit' => '0615-123456-123-4',
        ])->assertSessionHasErrors('nit');

        $this->assertDatabaseCount('clients', 1);
    }

    #[Test]
    public function a_client_with_receivables_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $client = $this->createClient();
        $this->createReceivable($client);

        $this->actingAs($admin)->delete(route('clients.destroy', $client))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    #[Test]
    public function a_client_without_receivables_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $client = $this->createClient();

        $this->actingAs($admin)->delete(route('clients.destroy', $client))
            ->assertRedirect(route('clients.index'));

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    #[Test]
    public function a_consultation_user_can_list_clients_but_cannot_manage_them(): void
    {
        $user = User::factory()->create(['role' => UserRole::Consulta]);
        $client = $this->createClient();

        $this->actingAs($user)->get(route('clients.index'))
            ->assertOk()
            ->assertSee($client->nombre);
        $this->actingAs($user)->get(route('clients.show', $client))->assertOk();

        $this->actingAs($user)->get(route('clients.create'))->assertForbidden();
        $this->actingAs($user)->post(route('clients.store'), ['nombre' => 'No permitido'])->assertForbidden();
        $this->actingAs($user)->delete(route('clients.destroy', $client))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createClient(array $attributes = []): Client
    {
        return Client::create(array_merge([
            'nombre' => 'Cliente de Prueba',
            'dui' => '01234567-8',
            'telefono' => '7000-0000',
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
