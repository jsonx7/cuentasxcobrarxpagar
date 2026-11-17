<?php

namespace Tests\Feature;

use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethod;
use App\Enums\ReceivableStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Material;
use App\Models\Payment;
use App\Models\PaymentMaterial;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MaterialManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_are_redirected_to_the_login_screen(): void
    {
        $this->get(route('materials.index'))->assertRedirect(route('login'));
        $this->get(route('materials.create'))->assertRedirect(route('login'));
    }

    #[Test]
    public function an_admin_can_register_a_material_with_a_default_unit(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('materials.store'), [
            'nombre' => 'Cemento Portland',
            'codigo' => 'CEM-01',
            'precio' => '12.50',
            'activo' => '1',
        ]);

        $material = Material::query()->firstOrFail();

        $response->assertRedirect(route('materials.show', $material));
        $this->assertSame('CEM-01', $material->codigo);
        $this->assertSame('unidad', $material->unidad);
        $this->assertEqualsWithDelta(12.50, (float) $material->precio, 0.001);
        $this->assertTrue($material->activo);
    }

    #[Test]
    public function the_material_code_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Material::create([
            'nombre' => 'Material Existente',
            'codigo' => 'CEM-01',
            'precio' => 10,
            'activo' => true,
        ]);

        $this->actingAs($admin)->post(route('materials.store'), [
            'nombre' => 'Material Duplicado',
            'codigo' => 'CEM-01',
            'precio' => 10,
        ])->assertSessionHasErrors('codigo');

        $this->assertDatabaseCount('materials', 1);
    }

    #[Test]
    public function a_material_can_be_updated_and_deactivated(): void
    {
        $admin = User::factory()->admin()->create();
        $material = Material::create([
            'nombre' => 'Material Original',
            'codigo' => 'MAT-001',
            'unidad' => 'unidad',
            'precio' => 10,
            'activo' => true,
        ]);

        $this->actingAs($admin)->put(route('materials.update', $material), [
            'nombre' => 'Material Actualizado',
            'codigo' => 'MAT-001',
            'unidad' => 'saco',
            'precio' => '15.75',
            'activo' => '0',
        ])->assertRedirect(route('materials.show', $material));

        $material->refresh();

        $this->assertSame('Material Actualizado', $material->nombre);
        $this->assertSame('saco', $material->unidad);
        $this->assertEqualsWithDelta(15.75, (float) $material->precio, 0.001);
        $this->assertFalse($material->activo);
    }

    #[Test]
    public function a_material_used_in_a_payment_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $material = Material::create([
            'nombre' => 'Arena',
            'codigo' => 'ARE-01',
            'precio' => 8,
            'activo' => true,
        ]);

        $client = Client::create([
            'nombre' => 'Cliente Materiales',
            'activo' => true,
        ]);
        $receivable = Receivable::create([
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
        $payment = Payment::create([
            'receivable_id' => $receivable->id,
            'fecha_pago' => now()->toDateString(),
            'monto' => 16,
            'metodo' => PaymentMethod::Material,
            'user_id' => $admin->id,
        ]);
        PaymentMaterial::create([
            'payment_id' => $payment->id,
            'material_id' => $material->id,
            'cantidad' => 2,
            'precio_unitario' => 8,
            'monto' => 16,
        ]);

        $this->actingAs($admin)->delete(route('materials.destroy', $material))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('materials', ['id' => $material->id]);
    }

    #[Test]
    public function a_consultation_user_can_list_materials_but_cannot_manage_them(): void
    {
        $user = User::factory()->create(['role' => UserRole::Consulta]);

        $this->actingAs($user)->get(route('materials.index'))->assertOk();
        $this->actingAs($user)->get(route('materials.create'))->assertForbidden();
        $this->actingAs($user)->post(route('materials.store'), [
            'nombre' => 'No permitido',
            'precio' => 1,
        ])->assertForbidden();
    }
}
