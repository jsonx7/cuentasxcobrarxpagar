<?php

namespace Tests\Unit;

use App\Enums\PaymentFrequency;
use App\Enums\ReceivableStatus;
use App\Models\Client;
use App\Models\Installment;
use App\Models\Receivable;
use App\Services\MoraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MoraServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_matches_the_excel_example_for_98_days(): void
    {
        $installment = $this->overdueInstallment(
            capital: 1111.92,
            monto: 1111.92,
            fechaVencimiento: '2026-01-01',
        );

        $mora = app(MoraService::class)->calculate($installment, '2026-04-09');

        $this->assertEqualsWithDelta(181.61, $mora, 0.005);
    }

    #[Test]
    public function it_matches_the_excel_example_for_one_day(): void
    {
        $installment = $this->overdueInstallment(
            capital: 833.05,
            monto: 833.05,
            fechaVencimiento: '2026-01-01',
        );

        $mora = app(MoraService::class)->calculate($installment, '2026-01-02');

        $this->assertEqualsWithDelta(1.39, $mora, 0.005);
    }

    #[Test]
    public function there_is_no_mora_until_the_installment_is_overdue(): void
    {
        $installment = $this->overdueInstallment(fechaVencimiento: '2026-01-01');
        $service = app(MoraService::class);

        $this->assertEqualsWithDelta(0, $service->calculate($installment, '2026-01-01'), 0.001);
        $this->assertEqualsWithDelta(0, $service->calculate($installment, '2025-12-31'), 0.001);
    }

    #[Test]
    public function a_fully_paid_installment_does_not_generate_mora(): void
    {
        $installment = $this->overdueInstallment(
            monto: 1000,
            montoPagado: 1000,
            fechaVencimiento: '2026-01-01',
        );

        $this->assertEqualsWithDelta(0, app(MoraService::class)->calculate($installment, '2026-03-01'), 0.001);
    }

    #[Test]
    public function pending_mora_subtracts_what_was_already_paid(): void
    {
        $installment = $this->overdueInstallment(
            monto: 1000,
            fechaVencimiento: now()->subDays(30)->toDateString(),
            moraPagada: 20,
        );
        $service = app(MoraService::class);

        $this->assertEqualsWithDelta(50, $service->calculate($installment, now()), 0.01);
        $this->assertEqualsWithDelta(30, $service->pendiente($installment, now()), 0.01);

        $totales = $service->totales($installment->receivable, now());

        $this->assertEqualsWithDelta(50, $totales['calculada'], 0.01);
        $this->assertEqualsWithDelta(20, $totales['pagada'], 0.01);
        $this->assertEqualsWithDelta(30, $totales['pendiente'], 0.01);
    }

    private function overdueInstallment(
        float $capital = 1000,
        float $monto = 1000,
        string $fechaVencimiento = '2026-01-01',
        float $montoPagado = 0,
        float $moraPagada = 0,
    ): Installment {
        $client = Client::create([
            'nombre' => 'Cliente Mora',
            'activo' => true,
        ]);

        $receivable = Receivable::create([
            'codigo' => 'CR-'.now()->year.'-'.str_pad((string) (Receivable::count() + 1), 4, '0', STR_PAD_LEFT),
            'client_id' => $client->id,
            'monto' => $monto,
            'tasa_interes_anual' => 0,
            'plazo' => 1,
            'frecuencia' => PaymentFrequency::Unico,
            'fecha_desembolso' => $fechaVencimiento,
            'fecha_primer_pago' => $fechaVencimiento,
            'cuota' => $monto,
            'interes_total' => 0,
            'total_a_pagar' => $monto,
            'tasa_mora_mensual' => 5,
            'estado' => ReceivableStatus::Activo,
        ]);

        return Installment::create([
            'receivable_id' => $receivable->id,
            'numero' => 1,
            'fecha_vencimiento' => $fechaVencimiento,
            'monto' => $monto,
            'interes' => 0,
            'capital' => $capital,
            'saldo_pendiente' => $monto,
            'abono_extra' => 0,
            'monto_pagado' => $montoPagado,
            'mora_pagada' => $moraPagada,
        ]);
    }
}
