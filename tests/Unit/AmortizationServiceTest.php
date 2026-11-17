<?php

namespace Tests\Unit;

use App\Enums\PaymentFrequency;
use App\Services\AmortizationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AmortizationServiceTest extends TestCase
{
    #[Test]
    public function it_builds_the_same_weekly_plan_as_the_excel_model(): void
    {
        $plan = app(AmortizationService::class)->build(
            monto: 1500,
            tasaInteresAnual: 8,
            plazo: 30,
            frecuencia: PaymentFrequency::Semanal,
            fechaPrimerPago: '2026-01-05',
        );

        $this->assertCount(30, $plan['rows']);
        $this->assertEqualsWithDelta(50.17, $plan['cuota'], 0.01);
        $this->assertEqualsWithDelta(0.33, $plan['rows'][0]['interes'], 0.001);
        $this->assertEqualsWithDelta(1450.16, $plan['rows'][0]['saldo_pendiente'], 0.01);
        $this->assertEqualsWithDelta(1500, $plan['total_a_pagar'] - $plan['interes_total'], 0.02);
        $this->assertEqualsWithDelta(0, $plan['rows'][29]['saldo_pendiente'], 0.001);
    }

    #[Test]
    public function it_supports_a_zero_interest_loan(): void
    {
        $plan = app(AmortizationService::class)->build(
            monto: 300,
            tasaInteresAnual: 0,
            plazo: 3,
            frecuencia: PaymentFrequency::Mensual,
            fechaPrimerPago: '2026-01-31',
        );

        $this->assertCount(3, $plan['rows']);
        $this->assertEqualsWithDelta(100, $plan['cuota'], 0.001);
        $this->assertEqualsWithDelta(0, $plan['interes_total'], 0.001);
        $this->assertEquals('2026-02-28', $plan['rows'][1]['fecha_vencimiento']);
        $this->assertEquals('2026-03-31', $plan['rows'][2]['fecha_vencimiento']);
    }

    #[Test]
    public function it_schedules_bisemanal_payments_every_fourteen_days(): void
    {
        $plan = app(AmortizationService::class)->build(
            monto: 1200,
            tasaInteresAnual: 8,
            plazo: 3,
            frecuencia: PaymentFrequency::Bisemanal,
            fechaPrimerPago: '2026-01-05',
        );

        $this->assertCount(3, $plan['rows']);
        $this->assertSame('2026-01-05', $plan['rows'][0]['fecha_vencimiento']);
        $this->assertSame('2026-01-19', $plan['rows'][1]['fecha_vencimiento']);
        $this->assertSame('2026-02-02', $plan['rows'][2]['fecha_vencimiento']);
    }

    #[Test]
    public function a_single_payment_forces_one_installment(): void
    {
        $plan = app(AmortizationService::class)->build(
            monto: 500,
            tasaInteresAnual: 12,
            plazo: 20,
            frecuencia: PaymentFrequency::Unico,
            fechaPrimerPago: '2026-02-01',
        );

        $this->assertCount(1, $plan['rows']);
        $this->assertEqualsWithDelta(500, $plan['total_a_pagar'] - $plan['interes_total'], 0.001);
    }
}
